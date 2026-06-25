import { Injectable, signal } from '@angular/core';

export interface Material {
  id: number;
  title: string;
  teacherName: string;
  className: string;
  fileType: 'PDF' | 'فيديو' | 'PPT' | 'صورة';
  size: string;
  date: string;
}

@Injectable({ providedIn: 'root' })
export class MaterialsService {

  private materials = signal<Material[]>([
    { id: 1, title: 'ملخص وحدة النحو',        teacherName: 'أ. سارة محمود',  className: 'الصف الأول - أ',  fileType: 'PDF',   size: '2.4 MB', date: '2026-06-10' },
    { id: 2, title: 'شرح الجبر بالفيديو',      teacherName: 'أ. أحمد فاروق',  className: 'الصف الأول - ب',  fileType: 'فيديو', size: '45 MB',  date: '2026-06-08' },
    { id: 3, title: 'عرض تقديمي: الخلية',      teacherName: 'أ. منى عبدالله', className: 'الصف الثاني - أ', fileType: 'PPT',   size: '5.1 MB', date: '2026-06-05' },
  ]);

  loadMaterials(): void {
    // TODO: استبدال هذا بطلب HTTP حقيقي لما يبقى عندنا Backend
  }

  getAll(): Material[] {
    return this.materials();
  }
}
