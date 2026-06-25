import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { StudentsService, Student, Parent } from '../core/services/students';
import { ClassesService } from '../core/services/classes';

@Component({
  selector: 'app-dashboard-students',
  imports: [CommonModule, FormsModule],
  templateUrl: './dashboard-students.html',
  styleUrl: './dashboard-students.css',
})
export class DashboardStudents implements OnInit {

  activeTab: 'students' | 'parents' = 'students';
  searchTerm: string = '';

  // ── Add Student Modal ──
  showAddModal = false;
  addForm: {
    name: string;
    className: string;
    parentName: string;
    parentPhone: string;
  } = this.emptyForm();
  formError = '';

  constructor(
    public studentsService: StudentsService,
    public classesService: ClassesService,
  ) {}

  ngOnInit(): void {
    this.studentsService.loadStudents();
    this.classesService.loadClasses();
  }

  setTab(tab: 'students' | 'parents'): void { this.activeTab = tab; }

  get filteredStudents(): Student[] {
    const q = this.searchTerm.trim().toLowerCase();
    if (!q) return this.studentsService.getAllStudents();
    return this.studentsService.getAllStudents().filter(s =>
      s.name.toLowerCase().includes(q) || s.className.toLowerCase().includes(q)
    );
  }

  get filteredParents(): Parent[] {
    const q = this.searchTerm.trim().toLowerCase();
    if (!q) return this.studentsService.getAllParents();
    return this.studentsService.getAllParents().filter(p =>
      p.name.toLowerCase().includes(q)
    );
  }

  tone(value: number, good: number, mid: number): string {
    return value >= good ? 'green' : value >= mid ? 'orange' : 'red';
  }

  // ── Add form logic ──
  openAddModal(): void {
    this.addForm  = this.emptyForm();
    this.formError = '';
    this.showAddModal = true;
  }

  submitAdd(): void {
    const f = this.addForm;
    if (!f.name.trim())       { this.formError = 'يرجى إدخال اسم الطالب'; return; }
    if (!f.className)         { this.formError = 'يرجى اختيار الفصل الدراسي'; return; }
    if (!f.parentName.trim()) { this.formError = 'يرجى إدخال اسم ولي الأمر'; return; }
    if (!f.parentPhone.trim()){ this.formError = 'يرجى إدخال رقم هاتف ولي الأمر'; return; }

    const allStudents = this.studentsService.getAllStudents();
    const allParents  = this.studentsService.getAllParents();
    const newStudentId = allStudents.length > 0 ? Math.max(...allStudents.map(s => s.id)) + 1 : 1;
    const newParentId  = allParents.length > 0  ? Math.max(...allParents.map(p => p.id))  + 1 : 1;

    this.studentsService.add({
      id: newStudentId,
      name: f.name.trim(),
      className: f.className,
      parentName: f.parentName.trim(),
      parentId: newParentId,
      avgGrade: 0,
      attendancePct: 0,
    });

    this.showAddModal = false;
  }

  private emptyForm() {
    return { name: '', className: '', parentName: '', parentPhone: '' };
  }
}
