import { Injectable, signal } from '@angular/core';

export type TaskType = 'quiz' | 'homework' | 'assignment';

export interface SchoolTask {
  id: number;
  title: string;
  type: TaskType;
  teacherName: string;
  className: string;
  date: string;
  submissions: number;
  totalStudents: number;
}

@Injectable({ providedIn: 'root' })
export class TasksService {

  private tasks = signal<SchoolTask[]>([
    { id: 1, title: 'اختبار قصير: الجمل الاسمية',     type: 'quiz',       teacherName: 'أ. سارة محمود',  className: 'الصف الأول - أ',  date: '2026-06-18', submissions: 26, totalStudents: 28 },
    { id: 2, title: 'واجب: معادلات الدرجة الأولى',     type: 'homework',   teacherName: 'أ. أحمد فاروق',  className: 'الصف الأول - ب',  date: '2026-06-17', submissions: 24, totalStudents: 26 },
    { id: 3, title: 'تجربة الضوء والانكسار',           type: 'assignment', teacherName: 'أ. منى عبدالله', className: 'الصف الثاني - أ', date: '2026-06-15', submissions: 30, totalStudents: 30 },
    { id: 4, title: 'Reading Comprehension Quiz',       type: 'quiz',       teacherName: 'أ. كريم سامي',   className: 'الصف الثاني - ب', date: '2026-06-14', submissions: 20, totalStudents: 27 },
  ]);

  loadTasks(): void {
    // TODO: استبدال هذا بطلب HTTP حقيقي لما يبقى عندنا Backend
  }

  getAll(): SchoolTask[] {
    return this.tasks();
  }

  getRecent(limit: number = 4): SchoolTask[] {
    return this.tasks().slice(0, limit);
  }

  typeLabel(type: TaskType): string {
    return type === 'quiz' ? 'كويز' : type === 'homework' ? 'واجب' : 'تكليف';
  }

  typeClass(type: TaskType): string {
    return type === 'quiz' ? 'tag-quiz' : type === 'homework' ? 'tag-hw' : 'tag-assign';
  }
}
