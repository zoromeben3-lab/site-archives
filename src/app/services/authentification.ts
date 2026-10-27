import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, tap } from 'rxjs';

export type Role = 'etudiant' | 'chef' | 'admin';

export interface Utilisateur {
  id?: number;
  prenom: string;
  nom: string;
  role: Role;
  email?: string;
  filiere_id?: number | null;
}

interface ReponseLogin {
  message: string;
  access_token: string;
  token_type: string;
  user: {
    id: number;
    name: string;
    prenom: string;
    nom: string;
    email: string;
    role: Role;
    filiere_id: number | null;
  };
}

@Injectable({ providedIn: 'root' })
export class Authentification {
  private apiUrl = 'http://127.0.0.1:8000/api';
  private cleUtilisateur = 'utilisateur';
  private cleJeton = 'jeton_acces';

  constructor(private http: HttpClient) {}

  /**
   * Envoie les identifiants à l'API Laravel.
   */
  seConnecterApi(identifiant: string, mdp: string, souvenir: boolean): Observable<ReponseLogin> {
    return this.http.post<ReponseLogin>(`${this.apiUrl}/login`, {
      identifiant: identifiant.trim(),
      mdp: mdp
    }).pipe(
      tap(reponse => {
        // En cas de succès retourné par Laravel, on stocke le token et l'utilisateur
        const stockage = souvenir ? localStorage : sessionStorage;
        this.deconnecter();

        stockage.setItem(this.cleUtilisateur, JSON.stringify({
          id: reponse.user.id,
          prenom: reponse.user.prenom,
          nom: reponse.user.nom,
          role: reponse.user.role,
          email: reponse.user.email,
          filiere_id: reponse.user.filiere_id
        }));

        stockage.setItem(this.cleJeton, reponse.access_token);
      })
    );
  }

  utilisateur(): Utilisateur | null {
    const texte = localStorage.getItem(this.cleUtilisateur) ?? sessionStorage.getItem(this.cleUtilisateur);
    if (!texte) return null;
    try {
      return JSON.parse(texte);
    } catch {
      return null;
    }
  }

  jeton(): string | null {
    return localStorage.getItem(this.cleJeton) ?? sessionStorage.getItem(this.cleJeton);
  }

  estConnecte(): boolean {
    return this.utilisateur() !== null && this.jeton() !== null;
  }

  role(): Role {
    return this.utilisateur()?.role ?? 'etudiant';
  }

  deconnecter() {
    localStorage.removeItem(this.cleUtilisateur);
    sessionStorage.removeItem(this.cleUtilisateur);
    localStorage.removeItem(this.cleJeton);
    sessionStorage.removeItem(this.cleJeton);
  }
}
