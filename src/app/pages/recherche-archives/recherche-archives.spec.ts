import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { provideHttpClient } from '@angular/common/http';
import { RechercheArchives } from './recherche-archives';

describe('RechercheArchives', () => {
  let component: RechercheArchives;
  let fixture: ComponentFixture<RechercheArchives>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [RechercheArchives],
      providers: [provideRouter([]), provideHttpClient()],
    }).compileComponents();

    fixture = TestBed.createComponent(RechercheArchives);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
