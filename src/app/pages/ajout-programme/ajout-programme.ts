import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { Donnees } from '../../services/donnees';

@Component({
  selector: 'app-ajout-programme',
  imports: [FormsModule, RouterLink],
  templateUrl: './ajout-programme.html',
  styleUrl: './ajout-programme.css'
})
export class AjoutProgramme {
  dossier = '';
  niveau = '';
  semestre = '';

  fichier: File | null = null;
  apercu = '';
  erreurFichier = '';
  survol = false;
  erreur = '';
  succes = '';
  chargement = false;

  readonly extensionsOk = ['jpg', 'jpeg', 'png'];
  readonly tailleMax = 5 * 1024 * 1024; // 5 Mo

  constructor(public donnees: Donnees) {}

  semestres(): string[] {
    return this.donnees.semestresDe(this.niveau);
  }

  changerNiveau(valeur: string) {
    this.niveau = valeur;
    this.semestre = '';
  }

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
    this.retirerFichier();
    if (!f) return;

    const extension = f.name.split('.').pop()?.toLowerCase() ?? '';
    if (!this.extensionsOk.includes(extension)) {
      this.erreurFichier = 'Format non accepté (JPG ou PNG).';
      return;
    }
    if (f.size > this.tailleMax) {
      this.erreurFichier = 'Image trop lourde (5 Mo maximum).';
      return;
    }
    this.fichier = f;
    this.apercu = URL.createObjectURL(f);
  }

  retirerFichier() {
    this.fichier = null;
    this.apercu = '';
    this.erreurFichier = '';
  }

  ajouter() {
    this.succes = '';

    if (!this.dossier || !this.niveau || !this.semestre) {
      this.erreur = 'Veuillez choisir la filière, le niveau et le semestre.';
      return;
    }
    if (!this.fichier) {
      this.erreur = 'Veuillez ajouter la photo du programme.';
      return;
    }

    this.erreur = '';
    this.chargement = true;
    this.donnees.ajouterProgrammeApi(this.dossier, this.semestre, this.fichier).subscribe({
      next: () => {
        this.chargement = false;
        this.succes = `Programme du ${this.semestre} enregistré.`;
        this.dossier = '';
        this.niveau = '';
        this.semestre = '';
        this.retirerFichier();
      },
      error: (err) => {
        this.chargement = false;
        const erreurs = err.error?.errors ? Object.values(err.error.errors).flat() as string[] : [];
        this.erreur = erreurs[0] ?? err.error?.message ?? 'Envoi impossible. Vérifiez la connexion au serveur.';
      }
    });
  }
}
