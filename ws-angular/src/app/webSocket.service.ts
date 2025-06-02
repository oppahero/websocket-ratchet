import { Injectable, OnDestroy } from '@angular/core';
import {
  BehaviorSubject,
  Observable,
  Observer,
  Subject,
  Subscription,
} from 'rxjs';

export interface WSData {
  user: string;
  message: string;
  group?: number;
}

@Injectable({
  providedIn: 'root',
})
export class WebSocketService implements OnDestroy {
  private websocket: WebSocket | null = null;
  private socketUrl = 'ws://localhost:8080';
  private connectionInterval: number = 5000; // Intervalo de reintento de conexión
  private connectionIntervalId: number | null = null;

  // Subjects para manejar los eventos del WebSocket
  private _onOpen$ = new Subject<Event>();
  private _onMessage$ = new Subject<MessageEvent<any>>();
  private _onError$ = new Subject<Event>();
  private _onClose$ = new Subject<CloseEvent>();
  private _connectionStatus$ = new BehaviorSubject<boolean>(false);

  // Observables públicos para que los componentes se suscriban
  public onOpen$: Observable<Event> = this._onOpen$.asObservable();
  public onMessage$: Observable<MessageEvent<any>> = this._onMessage$.asObservable();
  public onError$: Observable<Event> = this._onError$.asObservable();
  public onClose$: Observable<CloseEvent> = this._onClose$.asObservable();
  public connectionStatus$: Observable<boolean> = this._connectionStatus$.asObservable();

  constructor(){
    this.connect()
  }

  private connect(): void {
    if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
      return;
    }

    this.websocket = new WebSocket(this.socketUrl);

    this.websocket.onopen = (event) => {
      this._onOpen$.next(event);
      this._connectionStatus$.next(true);

      if (this.connectionIntervalId) {
        clearInterval(this.connectionIntervalId);
        this.connectionIntervalId = null;
      }
    };

    this.websocket.onmessage = (event) => this._onMessage$.next(event);
    this.websocket.onerror = (event) => this.handleError(event);
    this.websocket.onclose = (event) => this.handleClose(event);
  }

  private handleError(error: Event): void {
    this._onError$.next(error);
    this._connectionStatus$.next(false);
    console.error('Error de WebSocket:', error);
  }

  private handleClose(event: CloseEvent): void {
    this._onClose$.next(event);
    this._connectionStatus$.next(false);
    console.log('Conexión WebSocket cerrada:', event);

    // Reintentar la conexión después de un intervalo
    if (!this.connectionIntervalId) {
      // Solo iniciar el intervalo si no está ya en curso
      this.connectionIntervalId = window.setInterval(() => {
        this.connect();
      }, this.connectionInterval);
    }
  }

  /**
   * Envía un mensaje a través de la conexión WebSocket.
   * @param message El mensaje a enviar.
   */
  public sendMessage(message: string): void {
    if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
      this.websocket.send(message);
    } else {
      console.warn(
        'No se puede enviar el mensaje. WebSocket no está conectado.'
      );
      // Aquí se podria guardar el mensaje en un buffer y enviarlo cuando se abra la conexión.
    }
  }

  /**
   * Cierra la conexión WebSocket.
   */
  public closeConnection(): void {
    if (this.websocket) {
      this.websocket.close();
      this.websocket = null;
      this._connectionStatus$.next(false);

      if (this.connectionIntervalId) {
        clearInterval(this.connectionIntervalId);
        this.connectionIntervalId = null;
      }
    }
  }

  /**
   * Limpieza al destruir el servicio.
   */
  ngOnDestroy(): void {
    this.closeConnection();
    this._onOpen$.complete();
    this._onMessage$.complete();
    this._onError$.complete();
    this._onClose$.complete();
    this._connectionStatus$.complete();
  }
}
