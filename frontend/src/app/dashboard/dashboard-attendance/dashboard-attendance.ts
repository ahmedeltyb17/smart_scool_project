import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AttendanceService, ClassAttendanceToday } from '../core/services/attendance';

@Component({
  selector: 'app-dashboard-attendance',
  imports: [CommonModule],
  templateUrl: './dashboard-attendance.html',
  styleUrl: './dashboard-attendance.css',
})
export class DashboardAttendance implements OnInit {

  constructor(public attendanceService: AttendanceService) {}

  ngOnInit(): void {
    this.attendanceService.loadAttendance();
  }

  get records(): ClassAttendanceToday[] { return this.attendanceService.getTodayRecords(); }
  get avgPct(): number { return this.attendanceService.getAveragePct(); }
  get totalAbsent(): number { return this.attendanceService.getTotalAbsentToday(); }
  get pendingCount(): number { return this.attendanceService.getPendingClassesCount(); }

  // قيمة تجريبية لحالات التأخير لحد ما يبقى عندنا API حقيقي
  get lateCount(): number { return 4; }

  tone(pct: number): string {
    return pct >= 93 ? 'green' : pct >= 88 ? 'orange' : 'red';
  }

  exportReport(): void {
    // TODO: ربط زر التصدير بـ API حقيقي لاحقًا
    window.print();
  }
}
