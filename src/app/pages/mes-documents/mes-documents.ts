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

  constructor(private donnees: Donnees) {}

  liste(): Telechargement[] {
    return this.donnees.mesTelechargements();
  }

  demanderVider() {
    this.modaleVisible = true;
  }

  annulerVider() {
    this.modaleVisible = false;
  }

  confirmerVider() {
    this.donnees.viderTelechargements();
    this.modaleVisible = false;
  }
}
