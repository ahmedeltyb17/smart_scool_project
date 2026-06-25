import { Routes } from '@angular/router';
import { DASHBOARD_ROUTES } from './dashboard/dashboard.routes';

export const routes: Routes = [
  { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
  ...DASHBOARD_ROUTES,
];