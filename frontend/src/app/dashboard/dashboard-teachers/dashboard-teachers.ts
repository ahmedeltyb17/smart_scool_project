import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { TeachersService, Teacher } from '../core/services/teachers';
import { ClassesService } from '../core/services/classes';

@Component({
  selector: 'app-dashboard-teachers',
  imports: [CommonModule, FormsModule],
  templateUrl: './dashboard-teachers.html',
  styleUrl: './dashboard-teachers.css',
})
export class DashboardTeachers implements OnInit {

  // ── Filters ──
  searchTerm: string = '';
  filterStatus: string = 'all';

  // ── Detail modal ──
  selectedTeacher: Teacher | null = null;

  // ── Add Teacher Modal ──
  showAddModal = false;
  addForm: {
    name: string;
    subject: string;
    email: string;
    classes: string[];
    status: 'active' | 'leave';
  } = this.emptyForm();
  formError = '';

  constructor(
    public teachersService: TeachersService,
    public classesService: ClassesService,
  ) {}

  ngOnInit(): void {
    this.teachersService.loadTeachers();
    this.classesService.loadClasses();
  }

  get filteredTeachers(): Teacher[] {
    const q = this.searchTerm.trim().toLowerCase();
    return this.teachersService.getAll().filter(t => {
      const matchSearch = !q || t.name.toLowerCase().includes(q) || t.subject.toLowerCase().includes(q);
      const matchStatus = this.filterStatus === 'all' || t.status === this.filterStatus;
      return matchSearch && matchStatus;
    });
  }

  viewTeacher(t: Teacher): void { this.selectedTeacher = t; }
  closeModal(): void { this.selectedTeacher = null; }
  deleteTeacher(t: Teacher): void { this.teachersService.delete(t.id); }

  statusLabel(s: string): string { return s === 'active' ? 'نشط' : 'إجازة'; }
  statusClass(s: string): string { return s === 'active' ? 'green' : 'orange'; }

  // ── Add form logic ──
  openAddModal(): void {
    this.addForm  = this.emptyForm();
    this.formError = '';
    this.showAddModal = true;
  }

  toggleClass(className: string): void {
    const idx = this.addForm.classes.indexOf(className);
    if (idx === -1) this.addForm.classes.push(className);
    else this.addForm.classes.splice(idx, 1);
  }

  isClassSelected(className: string): boolean {
    return this.addForm.classes.includes(className);
  }

  submitAdd(): void {
    const f = this.addForm;
    if (!f.name.trim())    { this.formError = 'يرجى إدخال اسم المدرس'; return; }
    if (!f.subject.trim()) { this.formError = 'يرجى إدخال المادة الدراسية'; return; }
    if (!f.email.trim())   { this.formError = 'يرجى إدخال البريد الإلكتروني'; return; }
    if (!f.email.includes('@')) { this.formError = 'البريد الإلكتروني غير صحيح'; return; }

    const allTeachers = this.teachersService.getAll();
    const newId = allTeachers.length > 0 ? Math.max(...allTeachers.map(t => t.id)) + 1 : 1;

    this.teachersService.add({
      id: newId,
      name: f.name.trim(),
      subject: f.subject.trim(),
      email: f.email.trim(),
      classes: f.classes,
      status: f.status,
      quizzesCount: 0,
    });

    this.showAddModal = false;
  }

  private emptyForm() {
    return { name: '', subject: '', email: '', classes: [] as string[], status: 'active' as const };
  }
}
