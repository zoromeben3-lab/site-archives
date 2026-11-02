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
    return !!u && u.prenom === c.prenom && u.nom === c.nom;
  }

  changerRole(c: Compte, role: Role) {
    if (this.estMoi(c)) return;
    this.donnees.changerRoleApi(c.id, role).subscribe({
      next: () => {},
      error: () => this.donnees.changerRole(c.id, role)
    });
  }

  basculer(c: Compte) {
    if (this.estMoi(c)) return;
    this.donnees.basculerActif(c.id);
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

    this.erreur = '';
    const nouveauCompte = {
      prenom: this.prenom.trim(),
      nom: this.nom.trim(),
      identifiant: this.identifiant.trim(),
      role: this.role
    };

    this.donnees.ajouterCompteApi(nouveauCompte).subscribe({
      next: () => {
        this.succes = 'Compte créé avec succès sur le serveur.';
        this.prenom = '';
        this.nom = '';
        this.identifiant = '';
        this.role = 'etudiant';
      },
      error: () => {
        const message = this.donnees.ajouterCompte(nouveauCompte);
        if (message) {
          this.erreur = message;
        } else {
          this.succes = 'Compte créé (mode local).';
          this.prenom = '';
          this.nom = '';
          this.identifiant = '';
          this.role = 'etudiant';
        }
      }
    });
  }
}
