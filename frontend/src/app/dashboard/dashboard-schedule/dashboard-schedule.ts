import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { TeachersService, Teacher } from '../core/services/teachers';
import { ClassesService, SchoolClass } from '../core/services/classes';
import { ScheduleService, ScheduleSlot, DAYS, PERIODS, PERIOD_TIMES, DayName } from '../core/services/schedule';

@Component({
  selector: 'app-dashboard-schedule',
  imports: [CommonModule, FormsModule],
  templateUrl: './dashboard-schedule.html',
  styleUrl: './dashboard-schedule.css',
})
export class DashboardSchedule implements OnInit {

  // ─── View mode ───────────────────────────────
  viewMode: 'teacher' | 'class' = 'teacher';

  // ─── Selected entity ─────────────────────────
  selectedTeacherId: number = 0;
  selectedClassId: number   = 0;

  // ─── Constants ───────────────────────────────
  days   = DAYS;
  periods = PERIODS;
  periodTimes = PERIOD_TIMES;

  // ─── Add slot modal ──────────────────────────
  showAddModal = false;
  addForm: {
    day: DayName;
    period: number;
    subject: string;
    teacherId: number;
    classId: number;
    room: string;
  } = this.emptyForm();

  // ─── Delete confirm ──────────────────────────
  slotToDelete: ScheduleSlot | null = null;

  constructor(
    public teachersService: TeachersService,
    public classesService: ClassesService,
    public scheduleService: ScheduleService,
  ) {}

  ngOnInit(): void {
    this.teachersService.loadTeachers();
    this.classesService.loadClasses();
    const firstTeacher = this.teachersService.getAll()[0];
    const firstClass   = this.classesService.getAll()[0];
    if (firstTeacher) this.selectedTeacherId = firstTeacher.id;
    if (firstClass)   this.selectedClassId   = firstClass.id;
  }

  // ─── Current teacher / class objects ─────────
  get currentTeacher(): Teacher | undefined {
    return this.teachersService.getById(this.selectedTeacherId);
  }
  get currentClass(): SchoolClass | undefined {
    return this.classesService.getById(this.selectedClassId);
  }

  // ─── Grid helper ─────────────────────────────
  getCell(day: DayName, periodIdx: number): ScheduleSlot | null {
    if (this.viewMode === 'teacher') {
      return this.scheduleService.getSlot(day, periodIdx, 'teacher', this.selectedTeacherId);
    } else {
      return this.scheduleService.getSlot(day, periodIdx, 'class', this.selectedClassId);
    }
  }

  // ─── Stats helpers ────────────────────────────
  get currentSlots(): ScheduleSlot[] {
    if (this.viewMode === 'teacher') return this.scheduleService.getForTeacher(this.selectedTeacherId);
    return this.scheduleService.getForClass(this.selectedClassId);
  }

  get totalWeeklyPeriods(): number {
    return this.currentSlots.length;
  }

  get uniqueSubjects(): string[] {
    return [...new Set(this.currentSlots.map(s => s.subject))];
  }

  busyDays(): number {
    return new Set(this.currentSlots.map(s => s.day)).size;
  }

  // ─── Add slot ────────────────────────────────
  openAddModal(day?: DayName, periodIdx?: number): void {
    this.addForm = this.emptyForm();
    if (day      !== undefined) this.addForm.day    = day;
    if (periodIdx !== undefined) this.addForm.period = periodIdx;
    if (this.viewMode === 'teacher') this.addForm.teacherId = this.selectedTeacherId;
    if (this.viewMode === 'class')   this.addForm.classId   = this.selectedClassId;
    this.showAddModal = true;
  }

  submitAdd(): void {
    const f = this.addForm;
    if (!f.subject || !f.teacherId || !f.classId) return;

    const conflict = this.scheduleService.getSlot(f.day, f.period, 'teacher', f.teacherId) ||
                     this.scheduleService.getSlot(f.day, f.period, 'class',   f.classId);
    if (conflict) {
      alert('⚠️ يوجد تعارض في هذا الوقت! اختر وقتاً آخر.');
      return;
    }

    const teacher = this.teachersService.getById(f.teacherId);
    const cls     = this.classesService.getById(f.classId);
    this.scheduleService.add({
      day: f.day,
      period: f.period,
      subject: f.subject,
      teacherName: teacher?.name ?? '',
      teacherId: f.teacherId,
      className: cls?.name ?? '',
      classId: f.classId,
      room: f.room || undefined,
    });
    this.showAddModal = false;
  }

  // ─── Delete slot ─────────────────────────────
  confirmDelete(slot: ScheduleSlot): void {
    this.slotToDelete = slot;
  }

  doDelete(): void {
    if (this.slotToDelete) {
      this.scheduleService.delete(this.slotToDelete.id);
      this.slotToDelete = null;
    }
  }

  // ─── Subject colors ──────────────────────────
  subjectColor(subject: string): string {
    const map: Record<string, string> = {
      'اللغة العربية':    'arabic',
      'الرياضيات':        'math',
      'العلوم':           'science',
      'اللغة الإنجليزية': 'english',
      'الدراسات':         'social',
    };
    return map[subject] ?? 'other';
  }

  // ─── Util ────────────────────────────────────
  private emptyForm() {
    return {
      day:       DAYS[0] as DayName,
      period:    0,
      subject:   '',
      teacherId: 0,
      classId:   0,
      room:      '',
    };
  }
}
