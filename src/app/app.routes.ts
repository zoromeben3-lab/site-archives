import { Routes } from '@angular/router';

import { CadreConnexion } from './cadres/cadre-connexion/cadre-connexion';
import { CadrePrincipal } from './cadres/cadre-principal/cadre-principal';

import { Connexion } from './pages/connexion/connexion';
import { Inscription } from './pages/inscription/inscription';
import { TableauDeBord } from './pages/tableau-de-bord/tableau-de-bord';
import { RechercheArchives } from './pages/recherche-archives/recherche-archives';
import { Programme } from './pages/programme/programme';
import { DetailModule } from './pages/detail-module/detail-module';
import { AjoutArchive } from './pages/ajout-archive/ajout-archive';
import { GestionUtilisateurs } from './pages/gestion-utilisateurs/gestion-utilisateurs';
import { MesDocuments } from './pages/mes-documents/mes-documents';
import { MonProfil } from './pages/mon-profil/mon-profil';
import { AjoutProgramme } from './pages/ajout-programme/ajout-programme';
import { GestionModules } from './pages/gestion-modules/gestion-modules';

import { gardeConnecte, gardeRole } from './gardes/gardes';

export const routes: Routes = [
  { path: '', redirectTo: 'connexion', pathMatch: 'full' },

  {
    path: '',
    component: CadreConnexion,
    children: [
      { path: 'connexion', component: Connexion },
      { path: 'inscription', component: Inscription },
    ],
  },

  {
    path: '',
    component: CadrePrincipal,
    canActivate: [gardeConnecte],
    children: [
      { path: 'tableau-de-bord', component: TableauDeBord },
      { path: 'archives', component: RechercheArchives },
      { path: 'recherche-archives', redirectTo: 'archives', pathMatch: 'full' },
      { path: 'programme', component: Programme },
      { path: 'modules/:id', component: DetailModule },
      { path: 'mes-documents', component: MesDocuments },
      { path: 'mon-profil', component: MonProfil },
      { path: 'ajout-archive', component: AjoutArchive, canActivate: [gardeRole(['chef', 'admin'])] },
      { path: 'ajout-programme', component: AjoutProgramme, canActivate: [gardeRole(['chef', 'admin'])] },
      { path: 'gestion-modules', component: GestionModules, canActivate: [gardeRole(['admin'])] },
      { path: 'utilisateurs', component: GestionUtilisateurs, canActivate: [gardeRole(['admin'])] },
    ],
  },
];
