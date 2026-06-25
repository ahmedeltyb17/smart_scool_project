import { Injectable, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../../environments/environment';

export interface GradeDistributionBand {
  label: string;
  count: number;
  colorClass: string;
}

export interface ClassAverage {
  className: string;
  teacherName: string;
  avgPct: number;
}

@Injectable({ providedIn: 'root' })
export class GradesService {

  private studentGrades = signal<any[]>([]);

  constructor(private http: HttpClient) {}

  loadGrades(): void {
    this.http.get<any[]>(`${environment.apiUrl}/grades`)
      .subscribe(data => this.studentGrades.set(data));
  }

  getDistribution(): GradeDistributionBand[] {
    const grades = this.studentGrades();
    return [
      { label: 'Excellent (90-100)', count: grades.filter(s => s.avgGrade >= 90).length,                            colorClass: 'green'  },
      { label: 'Very Good (80-89)', count: grades.filter(s => s.avgGrade >= 80 && s.avgGrade < 90).length,          colorClass: 'blue'   },
      { label: 'Good (70-79)',      count: grades.filter(s => s.avgGrade >= 70 && s.avgGrade < 80).length,          colorClass: 'gold'   },
      { label: 'Pass (60-69)',      count: grades.filter(s => s.avgGrade >= 60 && s.avgGrade < 70).length,          colorClass: 'orange' },
      { label: 'Fail (below 60)',   count: grades.filter(s => s.avgGrade < 60).length,                              colorClass: 'red'    },
    ];
  }

  getTotalGradedStudents(): number {
    return this.studentGrades().length;
  }

  getNeedAttention(): any[] {
    return this.studentGrades().filter(s => s.avgGrade < 60);
  }

  getClassAverages(): ClassAverage[] {
    const map: Record<string, number[]> = {};
    this.studentGrades().forEach(s => {
      if (!map[s.className]) map[s.className] = [];
      map[s.className].push(s.avgGrade);
    });
    return Object.entries(map).map(([className, grades]) => ({
      className,
      teacherName: '',
      avgPct: Math.round(grades.reduce((a, b) => a + b, 0) / grades.length),
    }));
  }
}