import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { provideHttpClient } from '@angular/common/http';
import { TableauDeBord } from './tableau-de-bord';

describe('TableauDeBord', () => {
  let component: TableauDeBord;
  let fixture: ComponentFixture<TableauDeBord>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TableauDeBord],
      providers: [provideRouter([]), provideHttpClient()],
    }).compileComponents();

    fixture = TestBed.createComponent(TableauDeBord);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
