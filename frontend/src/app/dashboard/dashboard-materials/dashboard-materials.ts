import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MaterialsService, Material } from '../core/services/materials';

@Component({
  selector: 'app-dashboard-materials',
  imports: [CommonModule],
  templateUrl: './dashboard-materials.html',
  styleUrl: './dashboard-materials.css',
})
export class DashboardMaterials implements OnInit {

  constructor(public materialsService: MaterialsService) {}

  ngOnInit(): void {
    this.materialsService.loadMaterials();
  }

  get materials(): Material[] { return this.materialsService.getAll(); }

  fileIcon(type: string): string {
    switch (type) {
      case 'PDF': return 'fa-file-pdf';
      case 'فيديو': return 'fa-file-video';
      case 'PPT': return 'fa-file-powerpoint';
      case 'صورة': return 'fa-file-image';
      default: return 'fa-file';
    }
  }
}
