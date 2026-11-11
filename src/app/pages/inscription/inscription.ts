import { Component } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { Authentification } from '../../services/authentification';
import { Donnees } from '../../services/donnees';

@Component({
  selector: 'app-inscription',
  imports: [RouterLink, FormsModule],
  templateUrl: './inscription.html',
  styleUrl: './inscription.css'
})
export class Inscription {
  nom= '';
  prenom = '';
  identifiant = '';
  filiere = '';
  niveau = '';
  mdp = '';
  mdp2 = '';
  erreur = '';
  chargement = false;

  constructor(private auth: Authentification, private router: Router, private donnees: Donnees) {}

  creerCompte() {
    // 1. Champs vides
    if (!this.nom.trim() || !this.prenom.trim() || !this.identifiant.trim()
        || !this.filiere || !this.niveau || !this.mdp || !this.mdp2) {
      this.erreur = 'Veuillez remplir tous les champs.';
      return;
    }

    // 2. Identifiant : seulement des caractères sûrs, 3 à 30 caractères
    const motif = /^[a-zA-Z0-9._-]{3,30}$/;
    if (!motif.test(this.identifiant.trim())) {
      this.erreur = 'Identifiant : 3 à 30 caractères (lettres, chiffres, point, tiret).';
      return;
    }

    // 3. Longueur du mot de passe
    if (this.mdp.length < 8) {
      this.erreur = 'Le mot de passe doit contenir au moins 8 caractères.';
      return;
    }

    // 4. Les deux mots de passe identiques
    if (this.mdp !== this.mdp2) {
      this.erreur = 'Les mots de passe ne sont pas identiques.';
      return;
    }

    this.erreur = '';
    this.chargement = true;
    const filiere = this.donnees.filieres.find(f => f.dossier === this.filiere)?.nom ?? this.filiere;

    this.auth.inscrire({
      prenom: this.prenom.trim(),
      nom: this.nom.trim(),
      identifiant: this.identifiant.trim(),
      mdp: this.mdp,
      filiere
    }).subscribe({
      next: () => {
        this.chargement = false;
        this.donnees.rechargerApresConnexion();
        this.router.navigate(['/tableau-de-bord']);
      },
      error: (err) => {
        this.chargement = false;
        const erreurs = err.error?.errors ? Object.values(err.error.errors).flat() as string[] : [];
        this.erreur = erreurs[0] ?? err.error?.message ?? 'Impossible de joindre le serveur.';
      }
    });
  }
}