import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ClassesService, SchoolClass } from '../core/services/classes';
import { TeachersService } from '../core/services/teachers';

@Component({
  selector: 'app-dashboard-classes',
  imports: [CommonModule, FormsModule],
  templateUrl: './dashboard-classes.html',
  styleUrl: './dashboard-classes.css',
})
export class DashboardClasses implements OnInit {

  searchTerm: string = '';

  // ── Add Class Modal ──
  showAddModal = false;
  addForm: {
    name: string;
    grade: string;
    teacherId: number;
    studentsCount: number;
  } = this.emptyForm();
  formError = '';

  readonly grades = [
    'الأول الإعدادي',
    'الثاني الإعدادي',
    'الثالث الإعدادي',
    'الأول الثانوي',
    'الثاني الثانوي',
    'الثالث الثانوي',
  ];

  constructor(
    public classesService: ClassesService,
    public teachersService: TeachersService,
  ) {}

  ngOnInit(): void {
    this.classesService.loadClasses();
    this.teachersService.loadTeachers();
  }

  get filteredClasses(): SchoolClass[] {
    const q = this.searchTerm.trim().toLowerCase();
    if (!q) return this.classesService.getAll();
    return this.classesService.getAll().filter(c =>
      c.name.toLowerCase().includes(q) || c.teacherName.toLowerCase().includes(q)
    );
  }

  attendanceTone(pct: number): string {
    return pct >= 93 ? 'green' : pct >= 88 ? 'orange' : 'red';
  }

  teacherInitial(name: string): string {
    const parts = name.split(' ');
    return parts[1]?.[0] || 'م';
  }

  // ── Add form logic ──
  openAddModal(): void {
    this.addForm  = this.emptyForm();
    this.formError = '';
    this.showAddModal = true;
  }

  submitAdd(): void {
    const f = this.addForm;
    if (!f.name.trim())   { this.formError = 'يرجى إدخال اسم الفصل'; return; }
    if (!f.grade)         { this.formError = 'يرجى اختيار المرحلة الدراسية'; return; }
    if (!f.teacherId)     { this.formError = 'يرجى اختيار المدرس المسؤول'; return; }

    const teacher = this.teachersService.getById(f.teacherId);
    const allClasses = this.classesService.getAll();
    const newId = allClasses.length > 0 ? Math.max(...allClasses.map(c => c.id)) + 1 : 1;

    this.classesService.add({
      id: newId,
      name: f.name.trim(),
      grade: f.grade,
      teacherName: teacher?.name ?? '',
      studentsCount: f.studentsCount || 0,
      attendancePct: 0,
    });

    this.showAddModal = false;
  }

  private emptyForm() {
    return { name: '', grade: '', teacherId: 0, studentsCount: 0 };
  }
}
