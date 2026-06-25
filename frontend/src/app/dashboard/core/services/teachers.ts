import { Injectable, signal } from '@angular/core';

export interface Teacher {
  id: number;
  name: string;
  subject: string;
  classes: string[];
  status: 'active' | 'leave';
  quizzesCount: number;
  email: string;
  avatar?: string;
}

@Injectable({ providedIn: 'root' })
export class TeachersService {

  private teachers = signal<Teacher[]>([
    { id: 1, name: 'أ. سارة محمود',  subject: 'اللغة العربية',     classes: ['الصف الأول - أ'],  status: 'active', quizzesCount: 12, email: 'sara.mahmoud@school.eg' },
    { id: 2, name: 'أ. أحمد فاروق',  subject: 'الرياضيات',         classes: ['الصف الأول - ب'],  status: 'active', quizzesCount: 18, email: 'ahmed.farouk@school.eg' },
    { id: 3, name: 'أ. منى عبدالله', subject: 'العلوم',            classes: ['الصف الثاني - أ'], status: 'active', quizzesCount: 9,  email: 'mona.abdullah@school.eg' },
    { id: 4, name: 'أ. كريم سامي',   subject: 'اللغة الإنجليزية',  classes: ['الصف الثاني - ب'], status: 'leave',  quizzesCount: 14, email: 'karim.samy@school.eg' },
    { id: 5, name: 'أ. هبة الشريف',  subject: 'الدراسات',          classes: ['الصف الثالث - أ'], status: 'active', quizzesCount: 7,  email: 'heba.elsherif@school.eg' },
    { id: 6, name: 'أ. محمد عزت',    subject: 'الرياضيات',         classes: ['الصف الثالث - ب'], status: 'active', quizzesCount: 16, email: 'mohamed.ezzat@school.eg' },
  ]);

  loadTeachers(): void {
    // TODO: استبدال هذا بطلب HTTP حقيقي لما يبقى عندنا Backend
  }

  getAll(): Teacher[] {
    return this.teachers();
  }

  getById(id: number): Teacher | undefined {
    return this.teachers().find(t => t.id === id);
  }

  add(item: Teacher): void {
    this.teachers.update(list => [...list, item]);
  }

  delete(id: number): void {
    this.teachers.update(list => list.filter(t => t.id !== id));
  }

  setStatus(id: number, status: 'active' | 'leave'): void {
    this.teachers.update(list => list.map(t => t.id === id ? { ...t, status } : t));
  }
}
