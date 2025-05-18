import { Component, OnDestroy, OnInit } from '@angular/core';
import { Subscription } from 'rxjs';
import { WebSocketService } from './WebSocket.service';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './app.component.html',
})
export class AppComponent implements OnInit, OnDestroy {

  message: string = '';
  errorMessage: string = '';
  socketSubscription: Subscription | undefined;
  isClosed!: boolean;

  constructor(private webSocketService: WebSocketService) {}

  ngOnInit(): void {
    this.initConection();
  }

  ngOnDestroy(): void {
    if (this.socketSubscription) {
      this.socketSubscription.unsubscribe();
    }
  }

  initConection() {
    this.socketSubscription = this.webSocketService.connect().subscribe({
      next: (data) => {
        this.message = data;
        this.errorMessage = '';
      },
      error: (error) => {
        this.errorMessage = 'Ocurrió un error con el WebSocket.';
        this.isClosed = true;
      },
      complete: () => {
        console.log('Conexión del WebSocket cerrada.');
        this.isClosed = true;
        this.errorMessage = '';
      },
    });
  }
}
