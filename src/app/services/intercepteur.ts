import { inject } from '@angular/core';
import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { Router } from '@angular/router';
import { catchError, throwError } from 'rxjs';
import { API_URL } from '../config';

const CLES = ['utilisateur', 'jeton_acces'];

// Ajoute le jeton à chaque requête vers l'API ; si le serveur répond 401, on renvoie vers la connexion.
export const intercepteurApi: HttpInterceptorFn = (req, next) => {
  if (!req.url.startsWith(API_URL)) return next(req);

  const router = inject(Router);
  const jeton = localStorage.getItem('jeton_acces') ?? sessionStorage.getItem('jeton_acces');
  const requete = jeton ? req.clone({ setHeaders: { Authorization: `Bearer ${jeton}` } }) : req;

  return next(requete).pipe(
    catchError((err: HttpErrorResponse) => {
      if (err.status === 401 && !req.url.endsWith('/login')) {
        CLES.forEach(c => { localStorage.removeItem(c); sessionStorage.removeItem(c); });
        router.navigate(['/connexion']);
      }
      return throwError(() => err);
    })
  );
};
