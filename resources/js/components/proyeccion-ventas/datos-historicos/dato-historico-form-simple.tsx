import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { FormEvent, useState } from 'react';

const MESES = [
    { value: 1, label: 'Enero' },
    { value: 2, label: 'Febrero' },
    { value: 3, label: 'Marzo' },
    { value: 4, label: 'Abril' },
    { value: 5, label: 'Mayo' },
    { value: 6, label: 'Junio' },
    { value: 7, label: 'Julio' },
    { value: 8, label: 'Agosto' },
    { value: 9, label: 'Septiembre' },
    { value: 10, label: 'Octubre' },
    { value: 11, label: 'Noviembre' },
    { value: 12, label: 'Diciembre' },
];

export interface DatoHistoricoFormValues {
    anio: number;
    mes: number;
    monto: number;
}

interface DatoHistoricoFormProps {
    onSubmit: (values: DatoHistoricoFormValues) => void;
    isLoading?: boolean;
    isPeriodReadOnly?: boolean;
    nextPeriod?: {
        mes: number;
        anio: number;
    } | null;
}

export function DatoHistoricoFormSimple({
    onSubmit,
    isLoading = false,
    isPeriodReadOnly = false,
    nextPeriod,
}: DatoHistoricoFormProps) {
    const [mes, setMes] = useState<number>(
        nextPeriod?.mes || new Date().getMonth() + 1,
    );
    const [anio, setAnio] = useState<number>(
        nextPeriod?.anio || new Date().getFullYear(),
    );
    const [monto, setMonto] = useState<number>(0);

    const getMesNombre = (mesNum: number) => {
        return (
            MESES.find((m) => m.value === mesNum)?.label || mesNum.toString()
        );
    };

    const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        onSubmit({ anio, mes, monto });
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-4">
            {/* Período (Año y Mes) */}
            {isPeriodReadOnly && nextPeriod ? (
                <div className="space-y-2">
                    <Label>Período</Label>
                    <div className="rounded-md border bg-muted px-3 py-2 text-sm">
                        <span className="font-semibold">
                            {getMesNombre(nextPeriod.mes)} {nextPeriod.anio}
                        </span>
                    </div>
                    <p className="text-sm text-muted-foreground">
                        Este es el siguiente período en la cadena de datos
                        históricos.
                    </p>
                </div>
            ) : (
                <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-2">
                        <Label htmlFor="mes">Mes</Label>
                        <Select
                            value={mes.toString()}
                            onValueChange={(value) => setMes(parseInt(value))}
                        >
                            <SelectTrigger id="mes">
                                <SelectValue placeholder="Seleccione un mes" />
                            </SelectTrigger>
                            <SelectContent>
                                {MESES.map((m) => (
                                    <SelectItem
                                        key={m.value}
                                        value={m.value.toString()}
                                    >
                                        {m.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="anio">Año</Label>
                        <Input
                            id="anio"
                            type="number"
                            placeholder="2024"
                            value={anio}
                            onChange={(e) =>
                                setAnio(parseInt(e.target.value) || 0)
                            }
                            min="2000"
                            max="2100"
                            required
                        />
                    </div>
                </div>
            )}

            {/* Monto */}
            <div className="space-y-2">
                <Label htmlFor="monto">Monto</Label>
                <Input
                    id="monto"
                    type="number"
                    step="0.01"
                    placeholder="0.00"
                    value={monto}
                    onChange={(e) => setMonto(parseFloat(e.target.value) || 0)}
                    min="0"
                    required
                />
                <p className="text-sm text-muted-foreground">
                    Ingrese el monto de ventas para este período.
                </p>
            </div>

            {/* Botones de acción */}
            <div className="flex justify-end gap-2">
                <Button type="submit" disabled={isLoading}>
                    {isLoading ? 'Guardando...' : 'Guardar'}
                </Button>
            </div>
        </form>
    );
}
