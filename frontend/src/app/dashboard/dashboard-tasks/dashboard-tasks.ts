import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { TasksService, SchoolTask } from '../core/services/tasks';

@Component({
  selector: 'app-dashboard-tasks',
  imports: [CommonModule, FormsModule],
  templateUrl: './dashboard-tasks.html',
  styleUrl: './dashboard-tasks.css',
})
export class DashboardTasks implements OnInit {

  searchTerm: string = '';

  constructor(public tasksService: TasksService) {}

  ngOnInit(): void {
    this.tasksService.loadTasks();
  }

  get filteredTasks(): SchoolTask[] {
    const q = this.searchTerm.trim().toLowerCase();
    if (!q) return this.tasksService.getAll();
    return this.tasksService.getAll().filter(t =>
      t.title.toLowerCase().includes(q) ||
      t.teacherName.toLowerCase().includes(q) ||
      t.className.toLowerCase().includes(q)
    );
  }

  submissionPct(t: SchoolTask): number {
    return Math.round((t.submissions / t.totalStudents) * 100);
  }
}
