import { Injectable, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Authentification, Role } from './authentification';
import { Observable, tap } from 'rxjs';
import { API_URL } from '../config';

export interface Filiere { nom: string; dossier: string; }
export interface Module { id: number; nom: string; filiere: string; niveau: string; semestre: string; credits: number; }
export interface Archive {
  id: number;
  titre: string;
  type: string;
  filiere: string;
  module: string;
  niveau: string;
  semestre?: string;
  annee: string;
  date: string;
  fichier: string;
  apercu?: string;
  description?: string;
}
export interface Actualite { id: number; titre: string; texte: string; date: string; auteur?: string; }
export interface Compte { id: number; prenom: string; nom: string; identifiant: string; role: Role; actif: boolean; filiere_id?: number | null; }
export interface Telechargement { titre: string; fichier: string; date: string; }
export interface NouveauCompte { prenom: string; nom: string; identifiant: string; role: Role; filiere?: string; }

@Injectable({ providedIn: 'root' })
export class Donnees {
  private apiUrl = API_URL;

  // Listes fixes (référentiel de l'interface)
  readonly filieres: Filiere[] = [
    { nom: 'Génie Informatique', dossier: 'genie-informatique' },
    { nom: 'Réseaux et systèmes', dossier: 'reseaux-systemes' },
    { nom: 'Réseaux et télécommunications', dossier: 'reseaux-telecommunications' },
    { nom: 'Électricité industrielle', dossier: 'electricite-industrielle' },
    { nom: 'Électronique, électrotechnique et automatisme', dossier: 'electronique-electrotechnique-automatisme' }
  ];

  readonly niveaux = ['Licence 1', 'Licence 2', 'Licence 3'];
  readonly semestresParNiveau: Record<string, string[]> = {
    'Licence 1': ['Semestre 1', 'Semestre 2'],
    'Licence 2': ['Semestre 3', 'Semestre 4'],
    'Licence 3': ['Semestre 5', 'Semestre 6']
  };
  readonly types = ['Devoir', 'Test', 'Épreuve de session'];
  readonly annees = ['2024 - 2025', '2023 - 2024'];

  // Données venant de l'API (aucune donnée n'est gardée dans le navigateur)
  readonly modules = signal<Module[]>([]);
  readonly comptes = signal<Compte[]>([]);
  readonly archives = signal<Archive[]>([]);
  readonly actualites = signal<Actualite[]>([]);
  readonly telechargements = signal<Telechargement[]>([]);
  private readonly programmes = signal<Record<string, string>>({});

  constructor(private http: HttpClient, private auth: Authentification) {
    this.chargerTout();
  }

  // Tout est réservé aux connectés : on ne charge qu'avec une session ouverte
  private chargerTout() {
    if (!this.auth.estConnecte()) return;
    this.chargerModulesDepuisApi();
    this.chargerArchivesDepuisApi();
    this.chargerActualitesDepuisApi();
    this.chargerProgrammesDepuisApi();
    this.chargerComptesDepuisApi();
    this.chargerTelechargementsDepuisApi();
  }

  // À la déconnexion : aucune donnée ne reste en mémoire pour le compte suivant
  reinitialiser() {
    this.modules.set([]);
    this.comptes.set([]);
    this.archives.set([]);
    this.actualites.set([]);
    this.telechargements.set([]);
    this.programmes.set({});
  }

  semestresDe(niveau: string): string[] {
    return this.semestresParNiveau[niveau] ?? [];
  }

  // ---------- Archives ----------
  chargerArchivesDepuisApi() {
    this.http.get<{ data: { data: Archive[] } }>(`${this.apiUrl}/archives`).subscribe({
      next: (r) => this.archives.set(r.data?.data ?? []),
      error: (err) => console.error('Erreur chargement archives:', err)
    });
  }

  ajouterArchiveApi(formData: FormData): Observable<any> {
    return this.http.post(`${this.apiUrl}/archives`, formData).pipe(
      tap(() => this.chargerArchivesDepuisApi())
    );
  }

  supprimerArchiveApi(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/archives/${id}`).pipe(
      tap(() => this.chargerArchivesDepuisApi())
    );
  }

  // Le fichier exige une connexion : on le récupère avec le jeton, puis on l'ouvre ou on l'enregistre.
  private recupererFichier(a: { fichier: string }): Observable<Blob> {
    return this.http.get(a.fichier, { responseType: 'blob' });
  }

  telechargerFichier(a: { titre: string; fichier: string }) {
    this.recupererFichier(a).subscribe({
      next: (blob) => {
        const url = URL.createObjectURL(blob);
        const lien = document.createElement('a');
        lien.href = url;
        lien.download = a.titre;
        lien.click();
        setTimeout(() => URL.revokeObjectURL(url), 10000);
        this.chargerTelechargementsDepuisApi();
      },
      error: (err) => alert(err.status === 404
        ? 'Le fichier est introuvable sur le serveur.'
        : 'Téléchargement impossible. Vérifiez votre connexion.')
    });
  }

  apercuFichier(a: { fichier: string }) {
    // Onglet ouvert tout de suite (sinon bloqué par le navigateur), rempli à l'arrivée du fichier
    const onglet = window.open('', '_blank');
    this.recupererFichier(a).subscribe({
      next: (blob) => {
        if (onglet) onglet.location.href = URL.createObjectURL(blob);
      },
      error: () => onglet?.close()
    });
  }

  // ---------- Modules ----------
  chargerModulesDepuisApi() {
    this.http.get<{ data: any[] }>(`${this.apiUrl}/modules`).subscribe({
      next: (r) => this.modules.set((r.data ?? []).map(m => ({
        id: m.id,
        nom: m.nom,
        filiere: m.filiere?.nom ?? '',
        niveau: m.niveau?.intitule ?? m.niveau?.code ?? '',
        semestre: m.semestre ?? '',
        credits: m.credits ?? 6
      }))),
      error: (err) => console.error('Erreur chargement modules:', err)
    });
  }

  modulesDeFiliere(filiere: string, niveau = ''): string[] {
    const f = (filiere || '').trim().toLowerCase();
    const n = (niveau || '').trim().toLowerCase();
    return this.modules()
      .filter(m => (!f || m.filiere.trim().toLowerCase() === f) && (!n || m.niveau.trim().toLowerCase() === n))
      .map(m => m.nom);
  }

  ajouterModuleApi(m: Omit<Module, 'id'>): Observable<any> {
    return this.http.post(`${this.apiUrl}/admin/modules`, m).pipe(tap(() => this.chargerModulesDepuisApi()));
  }

  supprimerModuleApi(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/admin/modules/${id}`).pipe(tap(() => this.chargerModulesDepuisApi()));
  }

  // ---------- Programmes ----------
  private cleProgramme(nomFiliere: string, numeroSemestre: string): string {
    return `${nomFiliere}/${numeroSemestre.replace('Semestre ', '')}`;
  }

  chargerProgrammesDepuisApi() {
    this.http.get<{ data: { filiere: string; semestre: number; url: string }[] }>(`${this.apiUrl}/programmes`).subscribe({
      next: (r) => {
        const table: Record<string, string> = {};
        (r.data ?? []).forEach(p => (table[this.cleProgramme(p.filiere, String(p.semestre))] = p.url));
        this.programmes.set(table);
      },
      error: (err) => console.error('Erreur chargement programmes:', err)
    });
  }

  // Image envoyée par un chef/admin, sinon image par défaut du dossier public/programmes
  urlProgramme(dossier: string, semestre: string): string {
    const nom = this.filieres.find(f => f.dossier === dossier)?.nom ?? '';
    return this.programmes()[this.cleProgramme(nom, semestre)]
      ?? `/programmes/${dossier}/semestre-${semestre.replace('Semestre ', '')}.jpg`;
  }

  ajouterProgrammeApi(dossier: string, semestre: string, image: File): Observable<any> {
    const nom = this.filieres.find(f => f.dossier === dossier)?.nom ?? '';
    const donnees = new FormData();
    donnees.append('filiere', nom);
    donnees.append('semestre', semestre.replace('Semestre ', ''));
    donnees.append('image', image);
    return this.http.post(`${this.apiUrl}/programmes`, donnees).pipe(tap(() => this.chargerProgrammesDepuisApi()));
  }

  // ---------- Comptes (admin) ----------
  chargerComptesDepuisApi() {
    if (this.auth.role() !== 'admin' || !this.auth.estConnecte()) return;
    this.http.get<{ data: Compte[] }>(`${this.apiUrl}/admin/users`).subscribe({
      next: (r) => this.comptes.set(r.data ?? []),
      error: (err) => console.error('Erreur chargement comptes:', err)
    });
  }

  ajouterCompteApi(c: NouveauCompte): Observable<{ mot_de_passe_temporaire: string; data: Compte }> {
    return this.http.post<{ mot_de_passe_temporaire: string; data: Compte }>(`${this.apiUrl}/admin/users`, c).pipe(
      tap(() => this.chargerComptesDepuisApi())
    );
  }

  changerRoleApi(id: number, role: Role): Observable<any> {
    return this.http.patch(`${this.apiUrl}/admin/users/${id}`, { role }).pipe(tap(() => this.chargerComptesDepuisApi()));
  }

  basculerActifApi(c: Compte): Observable<any> {
    return this.http.patch(`${this.apiUrl}/admin/users/${c.id}`, { actif: !c.actif }).pipe(tap(() => this.chargerComptesDepuisApi()));
  }

  supprimerCompteApi(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/admin/users/${id}`).pipe(tap(() => this.chargerComptesDepuisApi()));
  }

  // ---------- Actualités ----------
  chargerActualitesDepuisApi() {
    this.http.get<{ data: Actualite[] }>(`${this.apiUrl}/actualites`).subscribe({
      next: (r) => this.actualites.set(r.data ?? []),
      error: (err) => console.error('Erreur chargement actualités:', err)
    });
  }

  ajouterActualiteApi(titre: string, texte: string): Observable<any> {
    return this.http.post(`${this.apiUrl}/admin/actualites`, { titre, texte }).pipe(tap(() => this.chargerActualitesDepuisApi()));
  }

  supprimerActualiteApi(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/admin/actualites/${id}`).pipe(tap(() => this.chargerActualitesDepuisApi()));
  }

  // ---------- Mes téléchargements ----------
  chargerTelechargementsDepuisApi() {
    if (!this.auth.estConnecte()) { this.telechargements.set([]); return; }
    this.http.get<{ data: Telechargement[] }>(`${this.apiUrl}/moi/telechargements`).subscribe({
      next: (r) => this.telechargements.set(r.data ?? []),
      error: (err) => console.error('Erreur chargement téléchargements:', err)
    });
  }

  viderTelechargementsApi(): Observable<any> {
    return this.http.delete(`${this.apiUrl}/moi/telechargements`).pipe(tap(() => this.telechargements.set([])));
  }

  // À appeler après connexion / déconnexion : recharge ce qui dépend du compte
  rechargerApresConnexion() {
    this.chargerTout();
  }
}
