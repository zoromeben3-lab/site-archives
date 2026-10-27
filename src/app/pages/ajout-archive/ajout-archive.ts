import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { Donnees } from '../../services/donnees';

@Component({
  selector: 'app-ajout-archive',
  imports: [FormsModule],
  templateUrl: './ajout-archive.html',
  styleUrl: './ajout-archive.css'
})
export class AjoutArchive {
  filiere = '';
  niveau = '';
  semestre = '';
  annee = '';
  module = '';
  type = '';
  titre = '';
  description = '';

  fichier: File | null = null;
  erreurFichier = '';
  survol = false;
  erreur = '';
  succes = '';
  envoiEnCours = false;

  readonly extensionsOk = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
  readonly tailleMax = 10 * 1024 * 1024; // 10 Mo

  constructor(public donnees: Donnees, private router: Router) {}

  // Les listes dépendantes
  semestres(): string[] {
    return this.donnees.semestresDe(this.niveau);
  }

  modules(): string[] {
    return this.donnees.modulesDeFiliere(this.filiere, this.niveau);
  }

  changerFiliere(valeur: string) {
    this.filiere = valeur;
    this.module = '';
  }

  changerNiveau(valeur: string) {
    this.niveau = valeur;
    this.semestre = '';
    this.module = '';
  }

  // Le fichier
  choisirFichier(event: Event) {
    const input = event.target as HTMLInputElement;
    this.verifier(input.files?.[0] ?? null);
  }

  deposer(event: DragEvent) {
    event.preventDefault();
    this.survol = false;
    this.verifier(event.dataTransfer?.files?.[0] ?? null);
  }

  survoler(event: DragEvent) {
    event.preventDefault();
    this.survol = true;
  }

  private verifier(f: File | null) {
    this.fichier = null;
    this.erreurFichier = '';
    if (!f) return;

    const extension = f.name.split('.').pop()?.toLowerCase() ?? '';
    if (!this.extensionsOk.includes(extension)) {
      this.erreurFichier = 'Format non accepté (PDF, DOC, DOCX, JPG, PNG).';
      return;
    }
    if (f.size > this.tailleMax) {
      this.erreurFichier = 'Fichier trop lourd (10 Mo maximum).';
      return;
    }
    this.fichier = f;
  }

  retirerFichier() {
    this.fichier = null;
    this.erreurFichier = '';
  }

  // Le formulaire avec envoi réel vers l'API Laravel
  ajouter() {
    this.succes = '';

    if (!this.filiere || !this.niveau || !this.annee ||
        !this.module || !this.type || !this.titre.trim()) {
      this.erreur = 'Veuillez remplir tous les champs obligatoires.';
      return;
    }
    if (!this.fichier) {
      this.erreur = 'Veuillez ajouter un fichier.';
      return;
    }

    this.erreur = '';
    this.envoiEnCours = true;

    // Construction du paquet multipart/form-data
    const formData = new FormData();
    formData.append('titre', this.titre.trim());
    formData.append('type', this.type === 'Épreuve de session' ? 'examen' : this.type.toLowerCase());
    formData.append('filiere', this.filiere);
    formData.append('module', this.module);
    formData.append('niveau', this.niveau);
    formData.append('annee', this.annee);
    formData.append('description', this.description);
    formData.append('file', this.fichier);

    const modTrouve = this.donnees.modules().find(m => m.nom.trim().toLowerCase() === this.module.trim().toLowerCase());
    if (modTrouve) {
      formData.append('module_id', modTrouve.id.toString());
    }

    // Envoi à l'API Laravel
    this.donnees.ajouterArchiveApi(formData).subscribe({
      next: (reponse) => {
        this.envoiEnCours = false;
        this.succes = 'Document archivé et enregistré avec succès sur le serveur !';
        this.reinitialiser();
        setTimeout(() => {
          this.router.navigate(['/archives']);
        }, 1500);
      },
      error: (err) => {
        this.envoiEnCours = false;
        if (err.status === 403) {
          this.erreur = 'Action non autorisée : en tant que chef de filière, vous ne pouvez déposer des épreuves que pour votre propre filière !';
        } else if (err.error && err.error.message) {
          this.erreur = err.error.message;
        } else {
          this.erreur = 'Une erreur est survenue lors de l\'envoi du fichier au serveur.';
        }
      }
    });
  }

  annuler() {
    this.router.navigate(['/tableau-de-bord']);
  }

  private reinitialiser() {
    this.filiere = '';
    this.niveau = '';
    this.semestre = '';
    this.annee = '';
    this.module = '';
    this.type = '';
    this.titre = '';
    this.description = '';
    this.fichier = null;
    this.erreurFichier = '';
  }
}
