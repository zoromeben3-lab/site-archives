import { Component } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { Authentification } from '../../services/authentification';

@Component({
  selector: 'app-connexion',
  imports: [RouterLink, FormsModule],
  templateUrl: './connexion.html',
  styleUrl: './connexion.css'
})
export class Connexion {
  identifiant = '';
  mdp = '';
  souvenir = false;
  erreur = '';
  afficherMdp = false;
  chargement = false;

  constructor(private router: Router, private auth: Authentification) {}

  basculerMdp() {
    this.afficherMdp = !this.afficherMdp;
  }

  seConnecter() {
    if (!this.identifiant.trim() || !this.mdp) {
      this.erreur = 'Veuillez saisir votre identifiant et votre mot de passe.';
      return;
    }

    this.erreur = '';
    this.chargement = true;

    // Appel réel vers l'API Laravel
    this.auth.seConnecterApi(this.identifiant, this.mdp, this.souvenir).subscribe({
      next: (reponse) => {
        this.chargement = false;
        // Connexion réussie -> Redirection vers le tableau de bord
        this.router.navigate(['/tableau-de-bord']);
      },
      error: (err) => {
        this.chargement = false;
        // Erreur renvoyée par Laravel (identifiant faux, mot de passe incorrect...)
        if (err.error && err.error.message) {
          this.erreur = err.error.message;
        } else {
          this.erreur = 'Impossible de joindre le serveur. Vérifiez que votre API Laravel est bien lancée.';
        }
      }
    });
  }
}
