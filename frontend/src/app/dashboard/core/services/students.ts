import { Injectable, signal } from '@angular/core';

export interface Student {
  id: number;
  name: string;
  className: string;
  parentName: string;
  parentId: number;
  avgGrade: number;
  attendancePct: number;
}

export interface Parent {
  id: number;
  name: string;
  phone: string;
  childrenNames: string[];
}

@Injectable({ providedIn: 'root' })
export class StudentsService {

  private students = signal<Student[]>([
    { id: 1, name: 'يوسف أحمد كمال',  className: 'الصف الأول - أ',  parentName: 'أحمد كمال',  parentId: 1, avgGrade: 92, attendancePct: 98 },
    { id: 2, name: 'نور محمد سيد',     className: 'الصف الأول - أ',  parentName: 'محمد سيد',   parentId: 2, avgGrade: 85, attendancePct: 94 },
    { id: 3, name: 'ملك عمرو فتحي',    className: 'الصف الثاني - ب', parentName: 'عمرو فتحي',  parentId: 3, avgGrade: 78, attendancePct: 82 },
    { id: 4, name: 'عمر خالد رشاد',    className: 'الصف الثالث - أ', parentName: 'خالد رشاد',  parentId: 4, avgGrade: 95, attendancePct: 99 },
    { id: 5, name: 'جنى وائل حسن',     className: 'الصف الثاني - أ', parentName: 'وائل حسن',   parentId: 5, avgGrade: 67, attendancePct: 75 },
  ]);

  private parents = signal<Parent[]>([
    { id: 1, name: 'أحمد كمال', phone: '01012345678', childrenNames: ['يوسف أحمد كمال'] },
    { id: 2, name: 'محمد سيد',  phone: '01023456789', childrenNames: ['نور محمد سيد'] },
    { id: 3, name: 'عمرو فتحي', phone: '01034567890', childrenNames: ['ملك عمرو فتحي'] },
    { id: 4, name: 'خالد رشاد', phone: '01045678901', childrenNames: ['عمر خالد رشاد'] },
    { id: 5, name: 'وائل حسن',  phone: '01056789012', childrenNames: ['جنى وائل حسن'] },
  ]);

  loadStudents(): void {
    // TODO: استبدال هذا بطلب HTTP حقيقي لما يبقى عندنا Backend
  }

  getAllStudents(): Student[] {
    return this.students();
  }

  getAllParents(): Parent[] {
    return this.parents();
  }


  add(item: Student): void {
    this.students.update(list => [...list, item]);
  }

  delete(id: number): void {
    this.students.update(list => list.filter(s => s.id !== id));
  }
  
  getStudentsNeedingAttention(): Student[] {
  return this.students().filter(s => s.avgGrade > 0 && s.avgGrade < 60);
}
}

