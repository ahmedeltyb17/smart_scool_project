import { Injectable, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../../environments/environment';

export interface ClassAttendanceToday {
  classId: number;
  className: string;
  teacherName: string;
  present: number;
  absent: number;
  attendancePct: number;
  uploaded: boolean;
}

export interface AttendanceTrendDay {
  day: string;
  pct: number;
}

@Injectable({ providedIn: 'root' })
export class AttendanceService {

  private records = signal<ClassAttendanceToday[]>([]);

  constructor(private http: HttpClient) {}

  loadAttendance(): void {
    this.http.get<ClassAttendanceToday[]>(`${environment.apiUrl}/attendance`)
      .subscribe(data => this.records.set(data));
  }

  getAll(): ClassAttendanceToday[]     { return this.records(); }
  getTodayRecords(): ClassAttendanceToday[] { return this.records(); }

  getAveragePct(): number {
    const uploaded = this.records().filter(r => r.uploaded);
    if (!uploaded.length) return 0;
    return Math.round(uploaded.reduce((s, r) => s + r.attendancePct, 0) / uploaded.length);
  }

  getTotalAbsentToday(): number {
    return this.records().reduce((s, r) => s + r.absent, 0);
  }

  getPendingClassesCount(): number {
    return this.records().filter(r => !r.uploaded).length;
  }

  get pendingCount(): number { return this.getPendingClassesCount(); }
  get avgPct(): number       { return this.getAveragePct(); }
  get totalAbsent(): number  { return this.getTotalAbsentToday(); }
  get lateCount(): number    { return 0; }

  getWeeklyTrend(): AttendanceTrendDay[] {
    return [
      { day: 'Sun', pct: 92 },
      { day: 'Mon', pct: 88 },
      { day: 'Tue', pct: 95 },
      { day: 'Wed', pct: 90 },
      { day: 'Thu', pct: 87 },
    ];
  }
}