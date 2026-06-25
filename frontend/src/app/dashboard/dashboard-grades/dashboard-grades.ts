import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { GradesService, GradeDistributionBand, ClassAverage } from '../core/services/grades';
import { StudentsService, Student } from '../core/services/students';

@Component({
  selector: 'app-dashboard-grades',
  imports: [CommonModule],
  templateUrl: './dashboard-grades.html',
  styleUrl: './dashboard-grades.css',
})
export class DashboardGrades implements OnInit {

  constructor(
    public gradesService: GradesService,
    public studentsService: StudentsService,
  ) {}

  ngOnInit(): void {
    this.gradesService.loadGrades();
    this.studentsService.loadStudents();
  }

  get distribution(): GradeDistributionBand[] { return this.gradesService.getDistribution(); }
  get classAverages(): ClassAverage[] { return this.gradesService.getClassAverages(); }
  get totalGraded(): number { return this.gradesService.getTotalGradedStudents(); }
  get needAttention(): Student[] { return this.studentsService.getStudentsNeedingAttention(); }

  distPct(count: number): number {
    return Math.round((count / (this.totalGraded || 1)) * 100);
  }

  avgTone(pct: number): string {
    return pct >= 85 ? 'green' : pct >= 70 ? 'orange' : 'red';
  }

  exportReport(): void {
    window.print();
  }
}
