import { Component } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { Archive, Donnees } from '../../services/donnees';

@Component({
  selector: 'app-detail-module',
  imports: [RouterLink],
  templateUrl: './detail-module.html',
  styleUrl: './detail-module.css'
})
export class DetailModule {
  module = '';
  onglets = ['Devoir', 'Test', 'Épreuve de session'];
  ongletActif = 'Devoir';

  constructor(route: ActivatedRoute, private donnees: Donnees) {
    this.module = route.snapshot.paramMap.get('id') ?? '';
  }

  resultats(): Archive[] {
    return this.donnees.archives().filter(a =>
      a.module === this.module && a.type === this.ongletActif
    );
  }

  telecharger(a: Archive) {
    this.donnees.telechargerFichier(a);
  }

  apercu(a: Archive) {
    this.donnees.apercuFichier(a);
  }
}
