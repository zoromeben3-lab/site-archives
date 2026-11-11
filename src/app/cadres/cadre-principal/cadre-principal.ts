import { Component } from '@angular/core';
import { Router, RouterOutlet, RouterLink, RouterLinkActive } from '@angular/router';
import { Authentification, Role } from '../../services/authentification';
import { Donnees } from '../../services/donnees';

const MENUS: Record<Role, { texte: string; lien: string }[]> = {
  etudiant: [
    { texte: 'Accueil', lien: '/tableau-de-bord' },
    { texte: 'Mes archives', lien: '/archives' },
    { texte: 'Programme', lien: '/programme' },
    { texte: 'Docs téléchargés', lien: '/mes-documents' },
    { texte: 'Mon profil', lien: '/mon-profil' }
  ],
  chef: [
    { texte: 'Tableau de bord', lien: '/tableau-de-bord' },
    { texte: 'Gestion des archives', lien: '/ajout-archive' },
    { texte: 'Gestion des programmes', lien: '/ajout-programme' },
    { texte: 'Archives', lien: '/archives' },
    { texte: 'Programme', lien: '/programme' },
    { texte: 'Mon profil', lien: '/mon-profil' }
  ],
  admin: [
    { texte: 'Tableau de bord', lien: '/tableau-de-bord' },
    { texte: 'Gestion des archives', lien: '/ajout-archive' },
    { texte: 'Gestion des programmes', lien: '/ajout-programme' },
    { texte: 'Utilisateurs', lien: '/utilisateurs' },
    { texte: 'Modules', lien: '/gestion-modules' },
    { texte: 'Archives', lien: '/archives' },
    { texte: 'Programme', lien: '/programme' },
    { texte: 'Mon profil', lien: '/mon-profil' }
  ]
};

const NOMS_ROLES: Record<Role, string> = {
  etudiant: 'Étudiant',
  chef: 'Chef de filière',
  admin: 'Administrateur'
};

@Component({
  selector: 'app-cadre-principal',
  imports: [RouterOutlet, RouterLink, RouterLinkActive],
  templateUrl: './cadre-principal.html',
  styleUrl: './cadre-principal.css'
})
export class CadrePrincipal {
  utilisateur = { nom: '', role: '', initiales: '' };
  menu: { texte: string; lien: string }[] = [];

  constructor(private auth: Authentification, private router: Router, private donnees: Donnees) {
    const u = this.auth.utilisateur();
    if (u) {
      this.utilisateur.nom = `${u.prenom} ${u.nom}`;
      this.utilisateur.role = NOMS_ROLES[u.role] ?? 'Étudiant';
      this.utilisateur.initiales = (u.prenom[0] + u.nom[0]).toUpperCase();
      this.menu = MENUS[u.role] ?? MENUS.etudiant;
    }
  }

  deconnexion() {
    this.auth.seDeconnecterApi().subscribe({ error: () => {} });
    this.auth.deconnecter();
    this.donnees.reinitialiser();
    this.router.navigate(['/connexion']);
  }
}
