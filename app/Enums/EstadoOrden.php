<?php

namespace App\Enums;

/**
 * Ciclo de vida de una orden de servicio.
 *
 * recibido → diagnostico → en_reparacion ⇄ espera_refacciones → terminado → entregado
 * Solo se cancela antes de empezar la reparación.
 */
enum EstadoOrden: string
{
    case Recibido = 'recibido';
    case Diagnostico = 'diagnostico';
    // Ya no se usa: solo queda para poder leer la bitácora de órdenes viejas. Ver enUso().
    case PorAutorizar = 'por_autorizar';
    case EnReparacion = 'en_reparacion';
    case EsperaRefacciones = 'espera_refacciones';
    case Terminado = 'terminado';
    case Entregado = 'entregado';
    case Cancelado = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::Recibido => 'Recibido',
            self::Diagnostico => 'Diagnóstico',
            self::PorAutorizar => 'Por autorizar',
            self::EnReparacion => 'En reparación',
            self::EsperaRefacciones => 'Espera de refacciones',
            self::Terminado => 'Terminado',
            self::Entregado => 'Entregado',
            self::Cancelado => 'Cancelado',
        };
    }

    /** @return list<self> estados a los que se puede pasar desde este. */
    public function siguientes(): array
    {
        return match ($this) {
            self::Recibido => [self::Diagnostico, self::Cancelado],
            self::Diagnostico => [self::EnReparacion, self::Cancelado],
            self::PorAutorizar => [],
            self::EnReparacion => [self::EsperaRefacciones, self::Terminado],
            self::EsperaRefacciones => [self::EnReparacion],
            self::Terminado => [self::Entregado],
            self::Entregado, self::Cancelado => [],
        };
    }

    public function puedePasarA(self $destino): bool
    {
        return in_array($destino, $this->siguientes(), true);
    }

    /** Una orden cerrada es de solo lectura (salvo reapertura por el administrador). */
    public function esCerrado(): bool
    {
        return in_array($this, [self::Entregado, self::Cancelado], true);
    }

    /**
     * Roles que pueden mover la orden a este estado. Lo usa OrdenServicio::cambiarEstado().
     *
     * @return list<Rol>
     */
    public function rolesQuePuedenEntrar(): array
    {
        return match ($this) {
            self::Diagnostico, self::EsperaRefacciones, self::Terminado => [Rol::Administrador, Rol::Mecanico],
            // Recepción registra la autorización del cliente; el mecánico la retoma al llegar la pieza.
            self::EnReparacion => [Rol::Administrador, Rol::Recepcionista, Rol::Mecanico],
            self::Recibido, self::Entregado, self::Cancelado => [Rol::Administrador, Rol::Recepcionista],
            self::PorAutorizar => [],
        };
    }

    /** ¿Se puede elegir hoy? Falso para estados retirados que solo existen en la bitácora. */
    public function enUso(): bool
    {
        return $this !== self::PorAutorizar;
    }

    /** @return list<self> estados que se muestran y se pueden elegir. */
    public static function enUsoTodos(): array
    {
        return array_values(array_filter(self::cases(), fn (self $e) => $e->enUso()));
    }

    /** @return list<self> estados de una orden abierta (vehículo dentro del taller). */
    public static function abiertos(): array
    {
        return array_values(array_filter(self::enUsoTodos(), fn (self $e) => ! $e->esCerrado()));
    }
}
