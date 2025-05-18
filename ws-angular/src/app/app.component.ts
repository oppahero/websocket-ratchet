import { Component, OnDestroy, OnInit } from '@angular/core';
import { Subscription } from 'rxjs';
import { CommonModule } from '@angular/common';
import { WebSocketService } from './webSocket.service';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './app.component.html',
})
export class AppComponent implements OnInit, OnDestroy {
  message: string = '';
  errorMessage: string = '';
  isClosed!: boolean;
  isConnected: boolean = false;
  connectionStatus: string = 'Desconectado';

  private onMessageSubscription: Subscription | undefined;
  private onOpenSubscription: Subscription | undefined;
  private onCloseSubscription: Subscription | undefined;
  private onErrorSubscription: Subscription | undefined;
  private connectionStatusSubscription: Subscription | undefined;

  constructor(private webSocketService: WebSocketService) {}

  ngOnInit(): void {
    this.onMessageSubscription = this.webSocketService.onMessage$.subscribe(
      (event: MessageEvent) => {
        this.message = event.data;
      }
    );

    this.onOpenSubscription = this.webSocketService.onOpen$.subscribe(() => {
      this.isConnected = true;
      this.isClosed = false;
    });

    this.onCloseSubscription = this.webSocketService.onClose$.subscribe(() => {
      this.isClosed = true;
      this.isConnected = false;
      this.message = '';
    });

    this.onErrorSubscription = this.webSocketService.onError$.subscribe(
      (error) => {
        this.isClosed = true;
        this.errorMessage = 'Ha ocurrido un error con la conexión al WS';
        this.message = '';
      }
    );

    this.connectionStatusSubscription =
      this.webSocketService.connectionStatus$.subscribe((status: boolean) => {
        this.connectionStatus = status ? 'Conectado' : 'Desconectado';
      });
  }

  ngOnDestroy(): void {
    if (this.onMessageSubscription) this.onMessageSubscription.unsubscribe();

    if (this.onOpenSubscription) this.onOpenSubscription.unsubscribe();

    if (this.onCloseSubscription) this.onCloseSubscription.unsubscribe();

    if (this.onErrorSubscription) this.onErrorSubscription.unsubscribe();

    if (this.connectionStatusSubscription)
      this.connectionStatusSubscription.unsubscribe();

    this.closeConnection()
  }

  closeConnection(): void {
    this.webSocketService.closeConnection();
  }
}
