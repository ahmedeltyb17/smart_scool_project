import { Injectable, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../../environments/environment';

export type DayName = 'Sunday' | 'Monday' | 'Tuesday' | 'Wednesday' | 'Thursday';
export const DAYS: DayName[] = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
export const PERIODS     = ['Period 1', 'Period 2', 'Period 3', 'Period 4', 'Period 5', 'Period 6'];
export const PERIOD_TIMES = ['8:00 - 8:45', '8:45 - 9:30', '9:45 - 10:30', '10:30 - 11:15', '11:30 - 12:15', '12:15 - 1:00'];

export interface ScheduleSlot {
  id: number;
  day: DayName;
  period: number;
  subject: string;
  teacherName: string;
  teacherId: number;
  className: string;
  classId: number;
  room?: string;
}

@Injectable({ providedIn: 'root' })
export class ScheduleService {

  private slots = signal<ScheduleSlot[]>([]);

  constructor(private http: HttpClient) {}

  loadSlots(): void {
    this.http.get<ScheduleSlot[]>(`${environment.apiUrl}/schedule`)
      .subscribe(data => this.slots.set(data));
  }

  getAll(): ScheduleSlot[] { return this.slots(); }

  getForTeacher(teacherId: number): ScheduleSlot[] {
    return this.slots().filter(s => s.teacherId === teacherId);
  }

  getForClass(classId: number): ScheduleSlot[] {
    return this.slots().filter(s => s.classId === classId);
  }

  getSlot(day: DayName, period: number, type: 'teacher' | 'class', id: number): ScheduleSlot | null {
    const field = type === 'teacher' ? 'teacherId' : 'classId';
    return this.slots().find(s => s.day === day && s.period === period && s[field] === id) ?? null;
  }

  add(slot: Omit<ScheduleSlot, 'id'>): void {
    this.http.post<ScheduleSlot>(`${environment.apiUrl}/schedule`, slot)
      .subscribe(s => this.slots.update(list => [...list, s]));
  }

  delete(id: number): void {
    this.http.delete(`${environment.apiUrl}/schedule/${id}`)
      .subscribe(() => this.slots.update(list => list.filter(s => s.id !== id)));
  }
}