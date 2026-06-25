import { Routes } from '@angular/router';
import { DashboardLayout } from './dashboard-layout/dashboard-layout';
import { DashboardHome } from './dashboard-home/dashboard-home';
import { DashboardClasses } from './dashboard-classes/dashboard-classes';
import { DashboardTeachers } from './dashboard-teachers/dashboard-teachers';
import { DashboardStudents } from './dashboard-students/dashboard-students';
import { DashboardAttendance } from './dashboard-attendance/dashboard-attendance';
import { DashboardGrades } from './dashboard-grades/dashboard-grades';
import { DashboardTasks } from './dashboard-tasks/dashboard-tasks';
import { DashboardMaterials } from './dashboard-materials/dashboard-materials';
import { DashboardSettings } from './dashboard-settings/dashboard-settings';
import { DashboardSchedule } from './dashboard-schedule/dashboard-schedule';

export const DASHBOARD_ROUTES: Routes = [
  {
    path: 'dashboard',
    component: DashboardLayout,
    children: [
      { path: '',            component: DashboardHome },
      { path: 'classes',     component: DashboardClasses },
      { path: 'teachers',    component: DashboardTeachers },
      { path: 'students',    component: DashboardStudents },
      { path: 'attendance',  component: DashboardAttendance },
      { path: 'grades',      component: DashboardGrades },
      { path: 'tasks',       component: DashboardTasks },
      { path: 'materials',   component: DashboardMaterials },
      { path: 'settings',    component: DashboardSettings },
      { path: 'schedule',    component: DashboardSchedule },
    ],
  },
];
