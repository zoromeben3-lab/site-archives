import { TestBed } from '@angular/core/testing';
import { provideHttpClient } from '@angular/common/http';
import { provideRouter } from '@angular/router';
import { Authentification } from './authentification';

describe('Authentification', () => {
  let service: Authentification;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideRouter([])]
    });
    service = TestBed.inject(Authentification);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
