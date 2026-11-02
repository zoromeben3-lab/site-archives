import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { provideHttpClient } from '@angular/common/http';
import { CadrePrincipal } from './cadre-principal';

describe('CadrePrincipal', () => {
  let component: CadrePrincipal;
  let fixture: ComponentFixture<CadrePrincipal>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [CadrePrincipal],
      providers: [provideRouter([]), provideHttpClient()],
    }).compileComponents();

    fixture = TestBed.createComponent(CadrePrincipal);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
