import { Component } from '@angular/core';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { Authentification, Role } from '../../services/authentification';
import { Actualite, Donnees } from '../../services/donnees';

const ACCES: Record<Role, { titre: string; detail: string; lien: string }[]> = {
  etudiant: [
    { titre: 'Rechercher des archives', detail: 'Devoirs, tests, épreuves', lien: '/archives' },
    { titre: 'Voir le programme', detail: "Programme de l'année", lien: '/programme' },
    { titre: 'Docs téléchargés', detail: 'Mes fichiers téléchargés', lien: '/mes-documents' },
    { titre: 'Mon profil', detail: 'Mes informations', lien: '/mon-profil' }
  ],
  chef: [
    { titre: 'Ajouter une archive', detail: 'Déposer un devoir, test ou épreuve', lien: '/ajout-archive' },
    { titre: 'Ajouter un programme', detail: 'Déposer la photo du programme', lien: '/ajout-programme' },
    { titre: 'Rechercher des archives', detail: 'Consulter les archives existantes', lien: '/archives' },
    { titre: 'Mon profil', detail: 'Mes informations', lien: '/mon-profil' }
  ],
  admin: [
    { titre: 'Gérer les utilisateurs', detail: 'Comptes et rôles', lien: '/utilisateurs' },
    { titre: 'Gérer les modules', detail: 'Modules par filière', lien: '/gestion-modules' },
    { titre: 'Ajouter une archive', detail: 'Déposer un fichier', lien: '/ajout-archive' },
    { titre: 'Ajouter un programme', detail: 'Déposer la photo du programme', lien: '/ajout-programme' }
  ]
};

const SOUS_TITRES: Record<Role, string> = {
  etudiant: 'Voici un aperçu de votre espace étudiant.',
  chef: 'Voici un aperçu de votre espace chef de filière.',
  admin: "Voici un aperçu de l'administration de la plateforme."
};

@Component({
  selector: 'app-tableau-de-bord',
  imports: [RouterLink, FormsModule],
  templateUrl: './tableau-de-bord.html',
  styleUrl: './tableau-de-bord.css'
})
export class TableauDeBord {
  prenom = '';
  sousTitre = '';
  estAdmin = false;
  accesRapides: { titre: string; detail: string; lien: string }[] = [];

  // Formulaire d'ajout pour l'administrateur
  formulaireActuOuvert = false;
  nouveauTitre = '';
  nouveauTexte = '';
  erreurActu = '';
  succesActu = '';
  actuASupprimer: Actualite | null = null;

  constructor(private auth: Authentification, public donnees: Donnees) {
    const u = this.auth.utilisateur();
    const role: Role = u?.role ?? 'etudiant';
    this.estAdmin = role === 'admin';
    this.prenom = u?.prenom ?? '';
    this.sousTitre = SOUS_TITRES[role];
    this.accesRapides = ACCES[role];
  }

  get actualites(): Actualite[] {
    return this.donnees.actualites();
  }

  publierActu() {
    this.erreurActu = '';
    this.succesActu = '';

    if (!this.nouveauTitre.trim() || !this.nouveauTexte.trim()) {
      this.erreurActu = 'Veuillez saisir un titre et un texte.';
      return;
    }

    this.donnees.ajouterActualiteApi(this.nouveauTitre.trim(), this.nouveauTexte.trim()).subscribe({
      next: () => {
        this.succesActu = 'Actualité publiée avec succès.';
        this.nouveauTitre = '';
        this.nouveauTexte = '';
        setTimeout(() => {
          this.succesActu = '';
          this.formulaireActuOuvert = false;
        }, 1200);
      },
      error: () => {
        this.erreurActu = 'Une erreur est survenue lors de la publication.';
      }
    });
  }

  demanderSuppressionActu(a: Actualite) {
    this.actuASupprimer = a;
  }

  annulerSuppressionActu() {
    this.actuASupprimer = null;
  }

  confirmerSuppressionActu() {
    if (!this.actuASupprimer) return;
    const id = this.actuASupprimer.id;
    this.actuASupprimer = null;
    this.donnees.supprimerActualiteApi(id).subscribe({
      next: () => {},
      error: (err) => console.error('Erreur suppression actualité:', err)
    });
  }
}
