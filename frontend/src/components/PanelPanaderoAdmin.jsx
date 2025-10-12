import React, { useState, useEffect } from 'react';
import '../styles/estilosPanadero.css';

const PanelPanaderoAdmin = () => {
    const [sueldoPorKg, setSueldoPorKg] = useState(0.30);
    const [panaderos, setPanaderos] = useState([
        {
            id: 1,
            nombre: "Panadero 1",
            totalProducido: 3000,
            mes: "Octubre 2024",
            sueldoAcumulado: 900.00
        },
        {
            id: 2,
            nombre: "Panadero 2",
            totalProducido: 4500,
            mes: "Octubre 2024",
            sueldoAcumulado: 1350.00
        }
    ]);

    const cambiarsueldo = () => {
        const nuevoSueldo = prompt("Ingrese el nuevo sueldo por kg:");
        if (nuevoSueldo) {
            setSueldoPorKg(parseFloat(nuevoSueldo));
        }
    };

    return (
        <div>
            <div className="Sueldo" style={{ margin: '1%' }}>
                <span>Sueldo por kg: </span><br />
                <span>Bs. {sueldoPorKg.toFixed(2)}</span>
                <button onClick={cambiarsueldo}>Cambiar Sueldo</button>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Panadero</th>
                        <th>Total Producido</th>
                        <th>Mes</th>
                        <th>Sueldo Acumulado</th>
                    </tr>
                </thead>
                <tbody>
                    {panaderos.map(panadero => (
                        <tr key={panadero.id}>
                            <td>{panadero.nombre}</td>
                            <td>{panadero.totalProducido}</td>
                            <td>{panadero.mes}</td>
                            <td>Bs. {panadero.sueldoAcumulado.toFixed(2)}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
};

export default PanelPanaderoAdmin;