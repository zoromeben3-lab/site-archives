import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { Archive, Donnees } from '../../services/donnees';

@Component({
  selector: 'app-recherche-archives',
  imports: [FormsModule, RouterLink],
  templateUrl: './recherche-archives.html',
  styleUrl: './recherche-archives.css'
})
export class RechercheArchives implements OnInit {
  recherche = '';
  onglets = ['Tous', 'Devoir', 'Test', 'Épreuve de session'];
  ongletActif = 'Tous';

  filiere = 'Toutes';
  module = 'Tous';
  niveau = 'Tous';
  annee = 'Toutes';

  constructor(public donnees: Donnees) {}

  ngOnInit() {
    this.donnees.chargerArchivesDepuisApi();
  }

  // Les modules proposés dépendent de la filière choisie
  modules(): string[] {
    return this.filiere === 'Toutes' ? [] : this.donnees.modulesDeFiliere(this.filiere);
  }

  changerFiliere(valeur: string) {
    this.filiere = valeur;
    this.module = 'Tous';
  }

  resultats(): Archive[] {
    const texte = this.recherche.trim().toLowerCase();
    return this.donnees.archives().filter(a => {
      const titreOk = !texte || (a.titre || '').toLowerCase().includes(texte) || (a.module || '').toLowerCase().includes(texte);

      let typeOk = true;
      if (this.ongletActif !== 'Tous') {
        const typeFiltre = this.ongletActif.toLowerCase();
        const typeArchive = (a.type || '').toLowerCase();
        if (typeFiltre.includes('session') || typeFiltre.includes('preuve')) {
          typeOk = typeArchive === 'examen' || typeArchive.includes('session');
        } else {
          typeOk = typeArchive === typeFiltre;
        }
      }

      const filiereOk = this.filiere === 'Toutes' || (a.filiere || '').toLowerCase() === this.filiere.toLowerCase();
      const moduleOk = this.module === 'Tous' || (a.module || '').toLowerCase() === this.module.toLowerCase();
      const niveauOk = this.niveau === 'Tous' || (a.niveau || '').toLowerCase() === this.niveau.toLowerCase();
      const anneeOk = this.annee === 'Toutes' || (a.annee || '').replace(/\s+/g, '') === this.annee.replace(/\s+/g, '');

      return titreOk && typeOk && filiereOk && moduleOk && niveauOk && anneeOk;
    });
  }

  telecharger(a: Archive) {
    this.donnees.enregistrerTelechargement(a.titre, a.fichier);
  }
}
