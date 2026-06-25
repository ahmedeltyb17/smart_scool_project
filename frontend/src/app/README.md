# لوحة تحكم مدير المدرسة — School Admin Dashboard

ده تحويل كامل لمشروع HerPower (Angular) إلى داش بورد لإدارة مدرسة، بنفس بنية الملفات والتصميم الأصلي (الألوان نفسها: وردي #D4667A، ذهبي #C9A96E، أزرق فاتح #5BA8C0، خلفية غامقة).

## طريقة التركيب

1. انسخ مجلد `dashboard/` بالكامل جوه مشروع الـ Angular بتاعك (نفس المكان اللي كان فيه الملفات القديمة).
2. لو كان عندك ملفات قديمة بنفس الاسم (dashboard-posts, dashboard-users, dashboard-comments) — احذفها أو سيبها لو لسه محتاجها لحاجة تانية.
3. افتح `app.routes.ts` الرئيسي بتاعك، وضيف الـ routes الموجودة في `dashboard/dashboard.routes.ts` (أو استورد `DASHBOARD_ROUTES` مباشرة لو شكل مشروعك بيسمح بكده).
4. تأكد إن مسار `app/core/services/` بيطابق نفس البنية في `tsconfig.json` بتاعك (الـ path alias `app/...`).

## هيكل الملفات

```
dashboard/
├── dashboard-layout/        ← القالب العام (Sidebar + Topbar)
├── dashboard-home/          ← الصفحة الرئيسية (إحصائيات عامة)
├── dashboard-classes/       ← إدارة الفصول
├── dashboard-teachers/      ← إدارة المدرسين
├── dashboard-students/      ← الطلاب وأولياء الأمور (تابات)
├── dashboard-attendance/    ← الحضور والغياب
├── dashboard-grades/        ← الدرجات
├── dashboard-tasks/         ← الكويزات والواجبات
├── dashboard-materials/     ← المواد التعليمية
├── dashboard-settings/      ← إعدادات المدرسة
├── dashboard.routes.ts      ← تعريف الـ routes جاهزة للنسخ
└── core/services/
    ├── classes.ts
    ├── teachers.ts
    ├── students.ts
    ├── attendance.ts
    ├── grades.ts
    ├── tasks.ts
    └── materials.ts
```

## ملاحظة مهمة — البيانات Mock فقط

كل الـ services دلوقتي بترجع بيانات تجريبية ثابتة (mock data) من خلال Angular Signals، عشان تقدر تشوف شكل الداش بورد كامل بدون باكند.

لما يبقى عندك API حقيقي، كل اللي محتاجه إنك تستبدل جوه كل service دالة `load...()` بطلب HTTP حقيقي، مثال:

```typescript
// بدل الكومنت ده:
loadClasses(): void {
  // TODO: استبدال هذا بطلب HTTP حقيقي لما يبقى عندنا Backend
}

// تحطه كده:
loadClasses(): void {
  this.http.get<SchoolClass[]>(`${API_BASE}/Admin/classes`, { headers: this.headers })
    .pipe(catchError(() => of([])))
    .subscribe(data => this.classes.set(data));
}
```

نفس الفكرة بالظبط مطبقة في كل service تاني (teachers, students, attendance, grades, tasks, materials).

## الصفحات وربطها بمصادر البيانات (مستقبلًا)

| الصفحة | الـ Endpoint المتوقع من الموبايل أبليكشن |
|---|---|
| الفصول | بيانات الفصول اللي الأدمن نفسه بيضيفها |
| المدرسون | بيانات المدرسين + ربطهم بفصول |
| الطلاب وأولياء الأمور | بيانات الطلاب + ربط ولي الأمر |
| الحضور والغياب | "uplode atendance" اللي المدرس بيرفعها من الموبايل |
| الدرجات | "uplode grades" اللي المدرس بيرفعها من الموبايل |
| الكويزات والواجبات | "uplode tasks" اللي المدرس بيرفعها من الموبايل |
| المواد التعليمية | "uploade mateirial" اللي المدرس بيرفعها من الموبايل |

يعني الأدمن داش بورد ده بيعمل "View" / مراجعة وتحكم في كل اللي المدرس بيرفعه من تطبيق الموبايل، مش رفع مباشر.
