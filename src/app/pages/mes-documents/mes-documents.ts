import { Component } from '@angular/core';
import { RouterLink } from '@angular/router';
import { Donnees, Telechargement } from '../../services/donnees';

@Component({
  selector: 'app-mes-documents',
  imports: [RouterLink],
  templateUrl: './mes-documents.html',
  styleUrl: './mes-documents.css'
})
export class MesDocuments {
  modaleVisible = false;

  constructor(private donnees: Donnees) {
    this.donnees.chargerTelechargementsDepuisApi();
  }

  retelecharger(d: Telechargement) {
    this.donnees.telechargerFichier(d);
  }

  liste(): Telechargement[] {
    return this.donnees.telechargements();
  }

  demanderVider() {
    this.modaleVisible = true;
  }

  annulerVider() {
    this.modaleVisible = false;
  }

  confirmerVider() {
    this.donnees.viderTelechargementsApi().subscribe();
    this.modaleVisible = false;
  }
}
