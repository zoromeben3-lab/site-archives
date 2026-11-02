import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { provideHttpClient } from '@angular/common/http';
import { CadreConnexion } from './cadre-connexion';

describe('CadreConnexion', () => {
  let component: CadreConnexion;
  let fixture: ComponentFixture<CadreConnexion>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [CadreConnexion],
      providers: [provideRouter([]), provideHttpClient()],
    }).compileComponents();

    fixture = TestBed.createComponent(CadreConnexion);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
