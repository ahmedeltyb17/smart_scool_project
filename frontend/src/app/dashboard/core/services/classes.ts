import { Injectable, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../../environments/environment';

export interface SchoolClass {
  id: number;
  name: string;
  grade: string;
  teacherName: string;
  studentsCount: number;
  attendancePct: number;
}

@Injectable({ providedIn: 'root' })
export class ClassesService {

  private classes = signal<SchoolClass[]>([]);

  constructor(private http: HttpClient) {}

  loadClasses(): void {
    this.http.get<SchoolClass[]>(`${environment.apiUrl}/classes`)
      .subscribe(data => this.classes.set(data));
  }

  getAll(): SchoolClass[] { return this.classes(); }

  getById(id: number): SchoolClass | undefined {
    return this.classes().find(c => c.id === id);
  }

  add(cls: any): void {
    this.http.post<SchoolClass>(`${environment.apiUrl}/classes`, cls)
      .subscribe(c => this.classes.update(list => [...list, c]));
  }

  delete(id: number): void {
    this.http.delete(`${environment.apiUrl}/classes/${id}`)
      .subscribe(() => this.classes.update(list => list.filter(c => c.id !== id)));
  }
}