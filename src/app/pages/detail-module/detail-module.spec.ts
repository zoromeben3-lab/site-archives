import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { provideHttpClient } from '@angular/common/http';
import { DetailModule } from './detail-module';

describe('DetailModule', () => {
  let component: DetailModule;
  let fixture: ComponentFixture<DetailModule>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [DetailModule],
      providers: [provideRouter([]), provideHttpClient()],
    }).compileComponents();

    fixture = TestBed.createComponent(DetailModule);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
