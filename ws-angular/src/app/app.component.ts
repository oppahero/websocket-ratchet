import { Component, OnDestroy, OnInit } from '@angular/core';
import { Subscription, timeInterval, timeout, timer } from 'rxjs';
import { CommonModule } from '@angular/common';
import { WebSocketService, WSData } from './webSocket.service';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './app.component.html',
})
export class AppComponent implements OnInit, OnDestroy {
  data!: any;
  message!: string | number | undefined;
  errorMessage: string = '';
  isClosed!: boolean;
  isConnected: boolean = false;
  connectionStatus: string = 'Desconectado';

  timeOutId!: any;

  private onMessageSubscription: Subscription | undefined;
  private onOpenSubscription: Subscription | undefined;
  private onCloseSubscription: Subscription | undefined;
  private onErrorSubscription: Subscription | undefined;
  private connectionStatusSubscription: Subscription | undefined;

  constructor(private webSocketService: WebSocketService) {}

  ngOnInit(): void {
    this.onMessageSubscription = this.webSocketService.onMessage$.subscribe(
      (event: MessageEvent<any>) => {
        this.data = event.data;
        this.delay(JSON.parse(event.data));
      }
    );

    this.onOpenSubscription = this.webSocketService.onOpen$.subscribe(() => {
      this.isConnected = true;
      this.isClosed = false;
    });

    this.onCloseSubscription = this.webSocketService.onClose$.subscribe(() => {
      this.isClosed = true;
      this.isConnected = false;
      this.data = undefined;
      this.message = undefined;
      this.errorMessage = '';
    });

    this.onErrorSubscription = this.webSocketService.onError$.subscribe(
      (error) => {
        this.data = undefined;
        this.message = undefined;
        this.isClosed = true;
        this.errorMessage = 'Ha ocurrido un error con la conexión al WS';
      }
    );

    this.connectionStatusSubscription =
      this.webSocketService.connectionStatus$.subscribe((status: boolean) => {
        this.connectionStatus = status ? 'Conectado' : 'Desconectado';
      });
  }

  delay(data: WSData) {
    clearTimeout(this.timeOutId);
    this.timeOutId = setTimeout(() => {
      this.message = data?.message;
    }, 3000);
  }

  ngOnDestroy(): void {
    if (this.onMessageSubscription) this.onMessageSubscription.unsubscribe();

    if (this.onOpenSubscription) this.onOpenSubscription.unsubscribe();

    if (this.onCloseSubscription) this.onCloseSubscription.unsubscribe();

    if (this.onErrorSubscription) this.onErrorSubscription.unsubscribe();

    if (this.connectionStatusSubscription)
      this.connectionStatusSubscription.unsubscribe();

    this.closeConnection();
  }

  closeConnection(): void {
    this.webSocketService.closeConnection();
  }
}
