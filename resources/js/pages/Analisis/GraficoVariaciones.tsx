import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { useState } from 'react';
import { LineChart, Line, BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import { TrendingUp, TrendingDown } from 'lucide-react';

interface Cuenta {
    id: number;
    codigo: string;
    nombre: string;
}

interface DatoGrafico {
    anio: number;
    valor: number;
    variacion_absoluta?: number;
    variacion_porcentual?: number;
}

interface Props {
    empresa: {
        id: number;
        nombre: string;
    };
    cuentas: Cuenta[];
    aniosDisponibles: number[];
    datos?: {
        cuenta: Cuenta;
        periodo: {
            inicio: number;
            fin: number;
        };
        datos: DatoGrafico[];
    };
}

export default function GraficoVariaciones({ empresa, cuentas, aniosDisponibles, datos }: Props) {
    const [cuentaSeleccionada, setCuentaSeleccionada] = useState<string>(
        datos?.cuenta.id.toString() || ''
    );
    const [anioInicio, setAnioInicio] = useState<string>(
        datos?.periodo.inicio.toString() || ''
    );
    const [anioFin, setAnioFin] = useState<string>(
        datos?.periodo.fin.toString() || ''
    );

    const breadcrumbs = [
        { title: 'Análisis', href: `/analisis/${empresa.id}` },
        { title: 'Gráfico de Variaciones', href: '#' },
    ];

    const formatearMoneda = (valor: number) => {
        return new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN',
            minimumFractionDigits: 2,
        }).format(valor);
    };

    const formatearPorcentaje = (valor: number) => {
        return `${valor >= 0 ? '+' : ''}${valor.toFixed(2)}%`;
    };

    const handleGenerarGrafico = () => {
        if (!cuentaSeleccionada || !anioInicio || !anioFin) {
            alert('Por favor selecciona todos los campos');
            return;
        }

        // Redirigir con los parámetros para cargar los datos
        window.location.href = `/analisis/${empresa.id}/grafico-variaciones?cuenta_id=${cuentaSeleccionada}&anio_inicio=${anioInicio}&anio_fin=${anioFin}`;
    };

    // Preparar datos para las gráficas si existen
    const datosGraficaValores = datos?.datos.map(d => ({
        name: d.anio.toString(),
        Valor: d.valor,
    })) || [];

    const datosGraficaVariaciones = datos?.datos
        .filter(d => d.variacion_absoluta !== undefined)
        .map(d => ({
            name: d.anio.toString(),
            'Var. Absoluta': d.variacion_absoluta,
            'Var. Porcentual': d.variacion_porcentual,
        })) || [];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Gráfico de Variaciones - ${empresa.nombre}`} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Título */}
                    <div className="mb-6">
                        <h2 className="text-2xl font-bold text-white">
                            Gráfico de Variaciones
                        </h2>
                        <p className="text-gray-300 mt-1">{empresa.nombre}</p>
                    </div>

                    {/* Card de selección */}
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle>Seleccionar Cuenta y Periodo</CardTitle>
                            <CardDescription>
                                Elige una cuenta y el periodo para visualizar sus variaciones
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                {/* Selector de cuenta */}
                                <div className="space-y-2">
                                    <Label htmlFor="cuenta">Cuenta</Label>
                                    <Select value={cuentaSeleccionada} onValueChange={setCuentaSeleccionada}>
                                        <SelectTrigger id="cuenta">
                                            <SelectValue placeholder="Selecciona una cuenta" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {cuentas.map((cuenta) => (
                                                <SelectItem key={cuenta.id} value={cuenta.id.toString()}>
                                                    {cuenta.codigo} - {cuenta.nombre}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                {/* Selector de año inicio */}
                                <div className="space-y-2">
                                    <Label htmlFor="anio-inicio">Año Inicio</Label>
                                    <Select value={anioInicio} onValueChange={setAnioInicio}>
                                        <SelectTrigger id="anio-inicio">
                                            <SelectValue placeholder="Año inicial" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {aniosDisponibles.map((anio) => (
                                                <SelectItem key={anio} value={anio.toString()}>
                                                    {anio}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                {/* Selector de año fin */}
                                <div className="space-y-2">
                                    <Label htmlFor="anio-fin">Año Fin</Label>
                                    <Select value={anioFin} onValueChange={setAnioFin}>
                                        <SelectTrigger id="anio-fin">
                                            <SelectValue placeholder="Año final" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {aniosDisponibles
                                                .filter(anio => !anioInicio || anio >= parseInt(anioInicio))
                                                .map((anio) => (
                                                    <SelectItem key={anio} value={anio.toString()}>
                                                        {anio}
                                                    </SelectItem>
                                                ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                {/* Botón generar */}
                                <div className="space-y-2">
                                    <Label className="invisible">Generar</Label>
                                    <Button 
                                        onClick={handleGenerarGrafico} 
                                        className="w-full"
                                        disabled={!cuentaSeleccionada || !anioInicio || !anioFin}
                                    >
                                        Generar Gráfico
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Mostrar gráficas si hay datos */}
                    {datos && (
                        <>
                            {/* Información de la cuenta */}
                            <Card className="mb-6">
                                <CardHeader>
                                    <CardTitle>
                                        {datos.cuenta.codigo} - {datos.cuenta.nombre}
                                    </CardTitle>
                                    <CardDescription>
                                        Periodo: {datos.periodo.inicio} - {datos.periodo.fin}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        {datos.datos.map((dato, index) => {
                                            const variacionAnterior = index > 0 ? datos.datos[index - 1] : null;
                                            const tendencia = variacionAnterior 
                                                ? dato.valor > variacionAnterior.valor
                                                : null;

                                            return (
                                                <Card key={dato.anio}>
                                                    <CardHeader className="pb-3">
                                                        <CardTitle className="text-lg">{dato.anio}</CardTitle>
                                                    </CardHeader>
                                                    <CardContent>
                                                        <div className="space-y-2">
                                                            <div>
                                                                <p className="text-sm text-muted-foreground">Valor</p>
                                                                <p className="text-2xl font-bold">
                                                                    {formatearMoneda(dato.valor)}
                                                                </p>
                                                            </div>
                                                            {dato.variacion_absoluta !== undefined && (
                                                                <>
                                                                    <div className="flex items-center gap-2">
                                                                        {tendencia !== null && (
                                                                            tendencia ? (
                                                                                <TrendingUp className="h-4 w-4 text-green-600" />
                                                                            ) : (
                                                                                <TrendingDown className="h-4 w-4 text-red-600" />
                                                                            )
                                                                        )}
                                                                        <div>
                                                                            <p className="text-sm text-muted-foreground">Variación</p>
                                                                            <p className={`font-semibold ${
                                                                                dato.variacion_absoluta > 0 
                                                                                    ? 'text-green-600' 
                                                                                    : dato.variacion_absoluta < 0 
                                                                                        ? 'text-red-600' 
                                                                                        : 'text-gray-600'
                                                                            }`}>
                                                                                {formatearMoneda(dato.variacion_absoluta)}
                                                                            </p>
                                                                            <p className={`text-sm ${
                                                                                dato.variacion_porcentual! > 0 
                                                                                    ? 'text-green-600' 
                                                                                    : dato.variacion_porcentual! < 0 
                                                                                        ? 'text-red-600' 
                                                                                        : 'text-gray-600'
                                                                            }`}>
                                                                                {formatearPorcentaje(dato.variacion_porcentual!)}
                                                                            </p>
                                                                        </div>
                                                                    </div>
                                                                </>
                                                            )}
                                                        </div>
                                                    </CardContent>
                                                </Card>
                                            );
                                        })}
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Gráfica de valores */}
                            <Card className="mb-6">
                                <CardHeader>
                                    <CardTitle>Evolución de Valores</CardTitle>
                                    <CardDescription>
                                        Valores de la cuenta por año
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <ResponsiveContainer width="100%" height={300}>
                                        <LineChart data={datosGraficaValores}>
                                            <CartesianGrid strokeDasharray="3 3" />
                                            <XAxis dataKey="name" />
                                            <YAxis />
                                            <Tooltip 
                                                formatter={(value: number) => formatearMoneda(value)}
                                            />
                                            <Legend />
                                            <Line 
                                                type="monotone" 
                                                dataKey="Valor" 
                                                stroke="#8884d8" 
                                                strokeWidth={2}
                                                dot={{ r: 5 }}
                                                activeDot={{ r: 8 }}
                                            />
                                        </LineChart>
                                    </ResponsiveContainer>
                                </CardContent>
                            </Card>

                            {/* Gráfica de variaciones */}
                            {datosGraficaVariaciones.length > 0 && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Variaciones Año a Año</CardTitle>
                                        <CardDescription>
                                            Cambios absolutos y porcentuales respecto al año anterior
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <ResponsiveContainer width="100%" height={300}>
                                            <BarChart data={datosGraficaVariaciones}>
                                                <CartesianGrid strokeDasharray="3 3" />
                                                <XAxis dataKey="name" />
                                                <YAxis yAxisId="left" orientation="left" />
                                                <YAxis yAxisId="right" orientation="right" />
                                                <Tooltip />
                                                <Legend />
                                                <Bar 
                                                    yAxisId="left"
                                                    dataKey="Var. Absoluta" 
                                                    fill="#82ca9d"
                                                />
                                                <Bar 
                                                    yAxisId="right"
                                                    dataKey="Var. Porcentual" 
                                                    fill="#8884d8"
                                                />
                                            </BarChart>
                                        </ResponsiveContainer>
                                    </CardContent>
                                </Card>
                            )}
                        </>
                    )}

                    {/* Mensaje cuando no hay datos */}
                    {!datos && (
                        <Card>
                            <CardContent className="py-12">
                                <div className="text-center text-muted-foreground">
                                    <p className="text-lg">Selecciona una cuenta y periodo para visualizar el gráfico</p>
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
