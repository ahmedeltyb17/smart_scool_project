import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { ClassesService, SchoolClass } from '../core/services/classes';
import { TeachersService } from '../core/services/teachers';
import { StudentsService } from '../core/services/students';
import { AttendanceService, AttendanceTrendDay } from '../core/services/attendance';
import { GradesService, GradeDistributionBand } from '../core/services/grades';
import { TasksService, SchoolTask } from '../core/services/tasks';

@Component({
  selector: 'app-dashboard-home',
  imports: [CommonModule, RouterLink],
  templateUrl: './dashboard-home.html',
  styleUrl: './dashboard-home.css',
})
export class DashboardHome implements OnInit {

  today = new Date().toLocaleDateString('ar-EG', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

  constructor(
    public classesService: ClassesService,
    public teachersService: TeachersService,
    public studentsService: StudentsService,
    public attendanceService: AttendanceService,
    public gradesService: GradesService,
    public tasksService: TasksService,
  ) {}

  ngOnInit(): void {
    this.classesService.loadClasses();
    this.teachersService.loadTeachers();
    this.studentsService.loadStudents();
    this.attendanceService.loadAttendance();
    this.gradesService.loadGrades();
    this.tasksService.loadTasks();
  }

  // ===== Stats =====
  get totalStudents(): number { return this.studentsService.getAllStudents().length + 154; } // قيمة تجريبية تمثيلية
  get totalTeachers(): number { return this.teachersService.getAll().length; }
  get totalClasses(): number { return this.classesService.getAll().length; }
  get avgAttendance(): number { return this.attendanceService.getAveragePct(); }

  // ===== Attendance trend =====
  get attendanceTrend(): AttendanceTrendDay[] { return this.attendanceService.getWeeklyTrend(); }
  get maxTrendValue(): number {
    return Math.max(...this.attendanceTrend.map(d => d.pct));
  }
  barHeight(pct: number): number {
    return Math.round((pct / this.maxTrendValue) * 100);
  }

  // ===== Grade distribution =====
  get gradeDistribution(): GradeDistributionBand[] { return this.gradesService.getDistribution(); }
  get totalGradedStudents(): number { return this.gradesService.getTotalGradedStudents(); }
  distPct(count: number): number {
    return Math.round((count / (this.totalGradedStudents || 1)) * 100);
  }

  // ===== Recent lists =====
  get recentTasks(): SchoolTask[] { return this.tasksService.getRecent(4); }
  get recentClasses(): SchoolClass[] { return this.classesService.getAll().slice(0, 4); }

  attendanceTone(pct: number): string {
    return pct >= 93 ? 'rose' : pct >= 88 ? 'gold' : 'dark';
  }
}
