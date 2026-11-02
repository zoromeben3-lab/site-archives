import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Donnees, Module } from '../../services/donnees';

@Component({
  selector: 'app-gestion-modules',
  imports: [FormsModule],
  templateUrl: './gestion-modules.html',
  styleUrl: './gestion-modules.css'
})
export class GestionModules implements OnInit {
  filiereFiltre = 'Toutes';

  nom = '';
  filiere = '';
  niveau = '';
  semestre = '';
  credits = 6;
  erreur = '';
  succes = '';

  moduleASupprimer: Module | null = null;

  constructor(public donnees: Donnees) {}

  ngOnInit() {
    this.donnees.chargerModulesDepuisApi();
  }

  semestres(): string[] {
    return this.donnees.semestresDe(this.niveau);
  }

  changerNiveau(valeur: string) {
    this.niveau = valeur;
    this.semestre = '';
  }

  liste(): Module[] {
    return this.donnees.modules()
      .filter(m => this.filiereFiltre === 'Toutes' || m.filiere === this.filiereFiltre)
      .sort((a, b) =>
        a.filiere.localeCompare(b.filiere) ||
        a.semestre.localeCompare(b.semestre) ||
        a.nom.localeCompare(b.nom));
  }

  ajouter() {
    this.succes = '';
    if (!this.nom.trim() || !this.filiere || !this.niveau || !this.semestre) {
      this.erreur = 'Veuillez remplir tous les champs.';
      return;
    }
    if (!Number.isInteger(this.credits) || this.credits < 1 || this.credits > 30) {
      this.erreur = 'Les crédits doivent être un nombre entier entre 1 et 30.';
      return;
    }
    const existe = this.donnees.modules().some(m =>
      m.filiere === this.filiere && m.nom.toLowerCase() === this.nom.trim().toLowerCase());
    if (existe) {
      this.erreur = 'Ce module existe déjà dans cette filière.';
      return;
    }

    this.erreur = '';
    const nouveauModule = {
      nom: this.nom.trim(),
      filiere: this.filiere,
      niveau: this.niveau,
      semestre: this.semestre,
      credits: this.credits
    };

    this.donnees.ajouterModuleApi(nouveauModule).subscribe({
      next: () => {
        this.succes = 'Module ajouté avec succès sur le serveur.';
        this.nom = '';
        this.semestre = '';
      },
      error: () => {
        // Fallback local si token ou API indisponible
        this.donnees.ajouterModule(nouveauModule);
        this.succes = 'Module ajouté localement.';
        this.nom = '';
        this.semestre = '';
      }
    });
  }

  demanderSuppression(m: Module) {
    this.moduleASupprimer = m;
  }

  annulerSuppression() {
    this.moduleASupprimer = null;
  }

  confirmerSuppression() {
    if (!this.moduleASupprimer) return;
    const id = this.moduleASupprimer.id;
    this.moduleASupprimer = null;
    this.donnees.supprimerModuleApi(id).subscribe({
      next: () => {},
      error: () => this.donnees.supprimerModule(id)
    });
  }
}
