import { Button } from '@/components/ui/button';
import {
    Form,
    FormControl,
    FormDescription,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { zodResolver } from '@hookform/resolvers/zod';
import { useForm } from 'react-hook-form';
import * as z from 'zod';

const MESES = [
    { value: '1', label: 'Enero' },
    { value: '2', label: 'Febrero' },
    { value: '3', label: 'Marzo' },
    { value: '4', label: 'Abril' },
    { value: '5', label: 'Mayo' },
    { value: '6', label: 'Junio' },
    { value: '7', label: 'Julio' },
    { value: '8', label: 'Agosto' },
    { value: '9', label: 'Septiembre' },
    { value: '10', label: 'Octubre' },
    { value: '11', label: 'Noviembre' },
    { value: '12', label: 'Diciembre' },
];

const formSchema = z.object({
    anio: z
        .number()
        .min(2000, 'El año debe ser mayor a 2000')
        .max(2100, 'El año debe ser menor a 2100'),
    mes: z.number().min(1, 'Mes inválido').max(12, 'Mes inválido'),
    monto: z.number().min(0, 'El monto debe ser mayor o igual a 0'),
});

export type DatoHistoricoFormValues = z.infer<typeof formSchema>;

interface DatoHistoricoFormProps {
    defaultValues?: Partial<DatoHistoricoFormValues>;
    onSubmit: (values: DatoHistoricoFormValues) => void;
    isLoading?: boolean;
    isReadOnly?: boolean;
    isPeriodReadOnly?: boolean;
    nextPeriod?: {
        mes: number;
        anio: number;
    } | null;
}

export function DatoHistoricoForm({
    defaultValues,
    onSubmit,
    isLoading = false,
    isReadOnly = false,
    isPeriodReadOnly = false,
    nextPeriod,
}: DatoHistoricoFormProps) {
    const form = useForm<DatoHistoricoFormValues>({
        resolver: zodResolver(formSchema),
        defaultValues: {
            anio:
                defaultValues?.anio ||
                nextPeriod?.anio ||
                new Date().getFullYear(),
            mes:
                defaultValues?.mes ||
                nextPeriod?.mes ||
                new Date().getMonth() + 1,
            monto: defaultValues?.monto || 0,
        },
    });

    const getMesNombre = (mes: number) => {
        return (
            MESES.find((m) => parseInt(m.value) === mes)?.label ||
            mes.toString()
        );
    };

    return (
        <Form {...form}>
            <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
                {/* Período (Año y Mes) */}
                {isPeriodReadOnly && nextPeriod ? (
                    <div className="space-y-2">
                        <FormLabel>Período</FormLabel>
                        <div className="rounded-md border bg-muted px-3 py-2 text-sm">
                            <span className="font-semibold">
                                {getMesNombre(nextPeriod.mes)} {nextPeriod.anio}
                            </span>
                        </div>
                        <FormDescription>
                            Este es el siguiente período en la cadena de datos
                            históricos.
                        </FormDescription>
                    </div>
                ) : (
                    <div className="grid grid-cols-2 gap-4">
                        <FormField
                            control={form.control}
                            name="mes"
                            render={({ field }) => (
                                <FormItem>
                                    <FormLabel>Mes</FormLabel>
                                    <Select
                                        onValueChange={(value) =>
                                            field.onChange(parseInt(value))
                                        }
                                        defaultValue={field.value?.toString()}
                                        disabled={isReadOnly}
                                    >
                                        <FormControl>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccione un mes" />
                                            </SelectTrigger>
                                        </FormControl>
                                        <SelectContent>
                                            {MESES.map((mes) => (
                                                <SelectItem
                                                    key={mes.value}
                                                    value={mes.value}
                                                >
                                                    {mes.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <FormMessage />
                                </FormItem>
                            )}
                        />

                        <FormField
                            control={form.control}
                            name="anio"
                            render={({ field }) => (
                                <FormItem>
                                    <FormLabel>Año</FormLabel>
                                    <FormControl>
                                        <Input
                                            type="number"
                                            placeholder="2024"
                                            {...field}
                                            onChange={(e) =>
                                                field.onChange(
                                                    parseInt(e.target.value),
                                                )
                                            }
                                            value={field.value}
                                            disabled={isReadOnly}
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            )}
                        />
                    </div>
                )}

                {/* Monto */}
                <FormField
                    control={form.control}
                    name="monto"
                    render={({ field }) => (
                        <FormItem>
                            <FormLabel>Monto</FormLabel>
                            <FormControl>
                                <Input
                                    type="number"
                                    step="0.01"
                                    placeholder="0.00"
                                    {...field}
                                    onChange={(e) =>
                                        field.onChange(
                                            parseFloat(e.target.value),
                                        )
                                    }
                                    value={field.value}
                                    disabled={isReadOnly}
                                />
                            </FormControl>
                            <FormDescription>
                                Ingrese el monto de ventas para este período.
                            </FormDescription>
                            <FormMessage />
                        </FormItem>
                    )}
                />

                {/* Botones de acción */}
                {!isReadOnly && (
                    <div className="flex justify-end gap-2">
                        <Button type="submit" disabled={isLoading}>
                            {isLoading ? 'Guardando...' : 'Guardar'}
                        </Button>
                    </div>
                )}
            </form>
        </Form>
    );
}
