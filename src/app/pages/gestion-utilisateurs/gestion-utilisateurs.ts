import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Authentification, Role } from '../../services/authentification';
import { Compte, Donnees } from '../../services/donnees';

@Component({
  selector: 'app-gestion-utilisateurs',
  imports: [FormsModule],
  templateUrl: './gestion-utilisateurs.html',
  styleUrl: './gestion-utilisateurs.css'
})
export class GestionUtilisateurs implements OnInit {
  recherche = '';
  roleFiltre = 'Tous';
  formulaireOuvert = false;

  prenom = '';
  nom = '';
  identifiant = '';
  role: Role = 'etudiant';
  filiere = '';
  motDePasseTemp = '';
  erreur = '';
  succes = '';

  readonly roles: { valeur: Role; texte: string }[] = [
    { valeur: 'etudiant', texte: 'Étudiant' },
    { valeur: 'chef', texte: 'Chef de filière' },
    { valeur: 'admin', texte: 'Administrateur' }
  ];

  constructor(public donnees: Donnees, private auth: Authentification) {}

  ngOnInit() {
    this.donnees.chargerComptesDepuisApi();
  }

  liste(): Compte[] {
    const texte = this.recherche.trim().toLowerCase();
    return this.donnees.comptes().filter(c =>
      (`${c.prenom} ${c.nom} ${c.identifiant}`.toLowerCase().includes(texte)) &&
      (this.roleFiltre === 'Tous' || c.role === this.roleFiltre)
    );
  }

  texteRole(role: Role): string {
    return this.roles.find(r => r.valeur === role)?.texte ?? role;
  }

  // On ne peut pas modifier son propre compte (évite de se retirer ses droits)
  estMoi(c: Compte): boolean {
    const u = this.auth.utilisateur();
    return !!u && u.id === c.id;
  }

  changerRole(c: Compte, role: Role) {
    if (this.estMoi(c)) return;
    this.erreur = '';
    this.donnees.changerRoleApi(c.id, role).subscribe({
      error: (err) => {
        this.erreur = err.error?.message ?? 'Changement de rôle impossible.';
        this.donnees.chargerComptesDepuisApi(); // remet la liste dans l'état réel
      }
    });
  }

  basculer(c: Compte) {
    if (this.estMoi(c)) return;
    this.erreur = '';
    this.donnees.basculerActifApi(c).subscribe({
      error: (err) => this.erreur = err.error?.message ?? 'Modification impossible.'
    });
  }

  ajouter() {
    this.succes = '';
    if (!this.prenom.trim() || !this.nom.trim() || !this.identifiant.trim()) {
      this.erreur = 'Veuillez remplir tous les champs.';
      return;
    }
    if (!/^[a-zA-Z0-9._-]{3,30}$/.test(this.identifiant.trim())) {
      this.erreur = 'Identifiant : 3 à 30 caractères (lettres, chiffres, point, tiret).';
      return;
    }

    if (this.role === 'chef' && !this.filiere) {
      this.erreur = 'Choisissez la filière du chef de filière.';
      return;
    }

    this.erreur = '';
    this.motDePasseTemp = '';
    const nouveauCompte = {
      prenom: this.prenom.trim(),
      nom: this.nom.trim(),
      identifiant: this.identifiant.trim(),
      role: this.role,
      filiere: this.role === 'chef' ? this.filiere : undefined
    };

    this.donnees.ajouterCompteApi(nouveauCompte).subscribe({
      next: (r) => {
        this.succes = 'Compte créé. Communiquez ce mot de passe temporaire à l\'utilisateur : il ne sera plus affiché.';
        this.motDePasseTemp = r.mot_de_passe_temporaire;
        this.prenom = '';
        this.nom = '';
        this.identifiant = '';
        this.role = 'etudiant';
        this.filiere = '';
      },
      error: (err) => {
        const erreurs = err.error?.errors ? Object.values(err.error.errors).flat() as string[] : [];
        this.erreur = erreurs[0] ?? err.error?.message ?? 'Création impossible. Vérifiez la connexion au serveur.';
      }
    });
  }
}
