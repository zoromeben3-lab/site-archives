import { Injectable, signal } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Authentification, Role } from './authentification';
import { Observable, tap } from 'rxjs';

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
  description?: string;
}
export interface Compte { id: number; prenom: string; nom: string; identifiant: string; role: Role; actif: boolean; }
export interface Telechargement { titre: string; fichier: string; date: string; }

const COMPTES_INIT: Compte[] = [
  { id: 1, prenom: 'Aïssatou', nom: 'Diallo', identifiant: 'etudiant', role: 'etudiant', actif: true },
  { id: 2, prenom: 'Moussa', nom: 'Traoré', identifiant: 'chef.info', role: 'chef', actif: true },
  { id: 3, prenom: 'Fatou', nom: 'Ouattara', identifiant: 'admin', role: 'admin', actif: true },
  { id: 4, prenom: 'Ibrahim', nom: 'Kaboré', identifiant: 'i.kabore', role: 'etudiant', actif: true },
  { id: 5, prenom: 'Aminata', nom: 'Sanogo', identifiant: 'a.sanogo', role: 'etudiant', actif: true }
];

@Injectable({ providedIn: 'root' })
export class Donnees {
  private apiUrl = 'http://127.0.0.1:8000/api';

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

  // Signals Angular réactifs
  readonly modules = signal<Module[]>(this.lire('modules', []));
  readonly comptes = signal<Compte[]>(this.lire('comptes', COMPTES_INIT));
  readonly archives = signal<Archive[]>([]);
  readonly programmes = signal<Record<string, string>>({});
  private readonly historique = signal<Record<string, Telechargement[]>>(this.lire('telechargements', {}));

  constructor(private http: HttpClient, private auth: Authentification) {
    this.chargerModulesDepuisApi();
    this.chargerArchivesDepuisApi();
  }

  // ---------- Utilitaires de stockage ----------
  private lire<T>(cle: string, defaut: T): T {
    try {
      const texte = localStorage.getItem('donnees.' + cle);
      return texte ? JSON.parse(texte) : defaut;
    } catch {
      return defaut;
    }
  }

  private ecrire(cle: string, valeur: unknown) {
    try {
      localStorage.setItem('donnees.' + cle, JSON.stringify(valeur));
    } catch {
      /* ignore */
    }
  }

  private prochainId(liste: { id: number }[]): number {
    return liste.reduce((max, x) => Math.max(max, x.id), 0) + 1;
  }

  private aujourdhui(): string {
    return new Date().toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
  }

  semestresDe(niveau: string): string[] {
    return this.semestresParNiveau[niveau] ?? [];
  }

  private obtenirOptionsAuth() {
    const jeton = this.auth.jeton();
    return {
      headers: new HttpHeaders(jeton ? { Authorization: `Bearer ${jeton}` } : {})
    };
  }

  // ---------- Chargement depuis l'API Laravel ----------
  chargerArchivesDepuisApi() {
    this.http.get<{ status: string; data: { data: Archive[] } }>(`${this.apiUrl}/archives`).subscribe({
      next: (reponse) => {
        if (reponse.data && reponse.data.data) {
          this.archives.set(reponse.data.data);
        }
      },
      error: (err) => console.error('Erreur chargement archives:', err)
    });
  }

  chargerModulesDepuisApi() {
    this.http.get<{ status: string; data: any[] }>(`${this.apiUrl}/modules`).subscribe({
      next: (reponse) => {
        if (reponse.data) {
          const formates: Module[] = reponse.data.map(m => ({
            id: m.id,
            nom: m.nom,
            filiere: m.filiere?.nom ?? '',
            niveau: m.niveau?.intitule ?? m.niveau?.code ?? '',
            semestre: 'Semestre 1',
            credits: 6
          }));
          this.modules.set(formates);
          this.ecrire('modules', formates);
        }
      },
      error: (err) => console.error('Erreur chargement modules:', err)
    });
  }

  // ---------- Modules ----------
  modulesDeFiliere(filiere: string, niveau = ''): string[] {
    const filiereCherchee = (filiere || '').trim().toLowerCase();
    const niveauCherche = (niveau || '').trim().toLowerCase();

    return this.modules()
      .filter(m => {
        const nomFiliere = (m.filiere || '').trim().toLowerCase();
        const nomNiveau = (m.niveau || '').trim().toLowerCase();

        const filiereOk = !filiereCherchee || nomFiliere === filiereCherchee;
        const niveauOk = !niveauCherche || nomNiveau === niveauCherche ||
          (niveauCherche.includes('1') && nomNiveau.includes('1')) ||
          (niveauCherche.includes('2') && nomNiveau.includes('2')) ||
          (niveauCherche.includes('3') && nomNiveau.includes('3'));

        return filiereOk && niveauOk;
      })
      .map(m => m.nom);
  }

  ajouterModule(m: Omit<Module, 'id'>) {
    const liste = [...this.modules(), { ...m, id: this.prochainId(this.modules()) }];
    this.modules.set(liste);
    this.ecrire('modules', liste);
  }

  supprimerModule(id: number) {
    const liste = this.modules().filter(m => m.id !== id);
    this.modules.set(liste);
    this.ecrire('modules', liste);
  }

  // ---------- Archives ----------
  ajouterArchiveApi(formData: FormData): Observable<any> {
    return this.http.post(`${this.apiUrl}/archives`, formData, this.obtenirOptionsAuth()).pipe(
      tap(() => {
        this.chargerArchivesDepuisApi();
      })
    );
  }

  ajouterArchive(a: Omit<Archive, 'id' | 'date'>) {
    this.archives.set([
      { ...a, id: this.prochainId(this.archives()), date: this.aujourdhui() },
      ...this.archives()
    ]);
  }

  supprimerArchiveApi(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/archives/${id}`, this.obtenirOptionsAuth()).pipe(
      tap(() => this.chargerArchivesDepuisApi())
    );
  }

  // ---------- Programmes ----------
  private cleProgramme(dossier: string, semestre: string): string {
    return `${dossier}/${semestre.replace('Semestre ', '')}`;
  }

  urlProgramme(dossier: string, semestre: string): string {
    const ajoute = this.programmes()[this.cleProgramme(dossier, semestre)];
    return ajoute ?? `/programmes/${dossier}/semestre-${semestre.replace('Semestre ', '')}.jpg`;
  }

  ajouterProgramme(dossier: string, semestre: string, url: string) {
    this.programmes.set({ ...this.programmes(), [this.cleProgramme(dossier, semestre)]: url });
  }

  // ---------- Comptes ----------
  ajouterCompte(c: Omit<Compte, 'id' | 'actif'>): string {
    const existe = this.comptes().some(x => x.identifiant.toLowerCase() === c.identifiant.toLowerCase());
    if (existe) return 'Cet identifiant est déjà utilisé.';
    const liste = [...this.comptes(), { ...c, id: this.prochainId(this.comptes()), actif: true }];
    this.comptes.set(liste);
    this.ecrire('comptes', liste);
    return '';
  }

  changerRole(id: number, role: Role) {
    const liste = this.comptes().map(c => (c.id === id ? { ...c, role } : c));
    this.comptes.set(liste);
    this.ecrire('comptes', liste);
  }

  basculerActif(id: number) {
    const liste = this.comptes().map(c => (c.id === id ? { ...c, actif: !c.actif } : c));
    this.comptes.set(liste);
    this.ecrire('comptes', liste);
  }

  // ---------- Téléchargements ----------
  private cleUtilisateur(): string {
    const u = this.auth.utilisateur();
    return u ? `${u.prenom} ${u.nom}` : '';
  }

  mesTelechargements(): Telechargement[] {
    return this.historique()[this.cleUtilisateur()] ?? [];
  }

  enregistrerTelechargement(titre: string, fichier: string) {
    const cle = this.cleUtilisateur();
    if (!cle) return;
    const deja = (this.historique()[cle] ?? []).filter(t => t.fichier !== fichier || t.titre !== titre);
    const tout = { ...this.historique(), [cle]: [{ titre, fichier, date: this.aujourdhui() }, ...deja] };
    this.historique.set(tout);
    this.ecrire('telechargements', tout);
  }

  viderTelechargements() {
    const tout = { ...this.historique(), [this.cleUtilisateur()]: [] };
    this.historique.set(tout);
    this.ecrire('telechargements', tout);
  }
}
