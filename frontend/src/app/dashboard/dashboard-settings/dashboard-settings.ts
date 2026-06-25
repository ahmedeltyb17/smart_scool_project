import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-dashboard-settings',
  imports: [CommonModule, FormsModule],
  templateUrl: './dashboard-settings.html',
  styleUrl: './dashboard-settings.css',
})
export class DashboardSettings {

  saved = false;

  form = {
    schoolName: 'مدرسة المستقبل الرسمية',
    academicYear: '2025 / 2026',
    currentTerm: 'الفصل الدراسي الثاني',
  };

  saveSettings(): void {
    // TODO: استبدال هذا بطلب HTTP حقيقي لما يبقى عندنا Backend
    // مثال: this.http.put(`${API_BASE}/Admin/school-settings`, this.form, { headers: this.headers })
    this.saved = true;
    setTimeout(() => { this.saved = false; }, 2000);
  }
}
