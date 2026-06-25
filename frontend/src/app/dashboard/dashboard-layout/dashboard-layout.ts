import { Component, HostListener, OnInit, AfterViewInit, ElementRef, ViewChild, ChangeDetectorRef, NgZone } from '@angular/core';
import { Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { CommonModule } from '@angular/common';
import { AttendanceService } from '../core/services/attendance';
// import { AuthService } from '../core/services/auth'; // فعّلها لما يبقى عندنا auth حقيقي

@Component({
  selector: 'app-dashboard-layout',
  imports: [CommonModule, RouterLink, RouterLinkActive, RouterOutlet],
  templateUrl: './dashboard-layout.html',
  styleUrl: './dashboard-layout.css',
})
export class DashboardLayout implements OnInit, AfterViewInit {
  collapsed  = false;
  mobileOpen = false;
  isMobile   = false;

  @ViewChild('sidebar', { static: true }) sidebarRef!: ElementRef<HTMLElement>;

  constructor(
    private router: Router,
    public attendanceService: AttendanceService,
    private cdr: ChangeDetectorRef,
    private ngZone: NgZone,
  ) {}

  // اسم وصورة مدير المدرسة — يتم ربطها بالـ AuthService لاحقًا
  adminName = 'أ. محمود سليم';
  adminRole = 'مدير المدرسة';
  get adminAvatar(): string {
    return 'https://i.pravatar.cc/150?img=12';
  }

  // ✅ عدد الفصول اللي لسه ما رفعتش الحضور النهاردة
  get pendingAttendanceCount(): number {
    return this.attendanceService.getPendingClassesCount();
  }

  private intervalRef: any;

  ngOnInit(): void {
    if (!localStorage.getItem('isAdmin')) this.router.navigate(['/login-dashboard']);
    this.onResize();
    this.attendanceService.loadAttendance();
    this.intervalRef = setInterval(() => {
      this.ngZone.run(() => this.cdr.detectChanges());
    }, 300);
  }

  ngAfterViewInit(): void {
    const sidebar = this.sidebarRef.nativeElement;
    sidebar.addEventListener('wheel', (e: WheelEvent) => {
      const el = sidebar;
      const atTop    = el.scrollTop === 0 && e.deltaY < 0;
      const atBottom = el.scrollTop + el.clientHeight >= el.scrollHeight && e.deltaY > 0;
      if (atTop || atBottom) e.preventDefault();
    }, { passive: false });

    sidebar.addEventListener('touchmove', (e: TouchEvent) => {
      e.stopPropagation();
    }, { passive: true });
  }

  get showLabels(): boolean {
    if (this.isMobile) return this.mobileOpen;
    return !this.collapsed;
  }

  toggleDesktop(): void { this.collapsed = !this.collapsed; }
  toggleSidebar(): void { this.mobileOpen = !this.mobileOpen; }

  onNavClick(): void {
    if (this.isMobile) this.mobileOpen = false;
  }

  logout(): void {
    clearInterval(this.intervalRef);
    localStorage.removeItem('isAdmin');
    this.router.navigate(['/login-dashboard']);
  }

  @HostListener('window:resize')
  onResize(): void {
    const w = window.innerWidth;
    const wasMobile = this.isMobile;
    this.isMobile = w <= 900;
    if (wasMobile && !this.isMobile) this.mobileOpen = false;
  }
}
