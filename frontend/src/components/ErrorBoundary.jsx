import React from 'react';
import { TriangleAlert } from 'lucide-react';
import { Container, Alert, Button } from 'react-bootstrap';

class ErrorBoundary extends React.Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false, error: null, errorInfo: null };
    }

    static getDerivedStateFromError() {
        // Actualizar estado para renderizar UI de error
        return { hasError: true };
    }

    componentDidCatch(error, errorInfo) {
        // Log del error para debugging
        console.error('Error capturado por ErrorBoundary:', error, errorInfo);

        this.setState({
            error,
            errorInfo
        });

        // TODO: Enviar a servicio de monitoreo (Sentry, LogRocket, etc)
        // if (import.meta.env.PROD) {
        //   sendToErrorTracking(error, errorInfo);
        // }
    }

    handleReload = () => {
        window.location.reload();
    };

    handleGoHome = () => {
        window.location.href = '/';
    };

    render() {
        if (this.state.hasError) {
            return (
                <Container className="py-5">
                    <div style={{ maxWidth: '600px', margin: '0 auto', textAlign: 'center' }}>
                        <TriangleAlert size={64} color="#8b6f47" style={{ marginBottom: '1rem' }} />
                        <h2 style={{ color: '#534031', marginBottom: '1rem' }}>¡Ups! Algo salió mal</h2>
                        <p className="text-muted" style={{ marginBottom: '1.5rem' }}>
                            Lo sentimos, ha ocurrido un error inesperado. Por favor, intenta recargar la página.
                        </p>

                        {import.meta.env.DEV && this.state.error && (
                            <Alert variant="danger" className="text-start" style={{ marginTop: '2rem' }}>
                                <Alert.Heading>Detalles del error (solo en desarrollo):</Alert.Heading>
                                <p style={{ fontSize: '0.9rem', fontFamily: 'monospace' }}>
                                    {this.state.error.toString()}
                                </p>
                                {this.state.errorInfo && (
                                    <details style={{ marginTop: '1rem' }}>
                                        <summary style={{ cursor: 'pointer' }}>Stack trace</summary>
                                        <pre style={{ fontSize: '0.75rem', overflow: 'auto', maxHeight: '200px' }}>
                                            {this.state.errorInfo.componentStack}
                                        </pre>
                                    </details>
                                )}
                            </Alert>
                        )}

                        <div style={{ marginTop: '2rem', display: 'flex', gap: '1rem', justifyContent: 'center' }}>
                            <Button
                                variant="primary"
                                onClick={this.handleGoHome}
                                style={{ backgroundColor: '#8b6f47', border: 'none' }}
                            >
                                Volver al inicio
                            </Button>
                            <Button
                                variant="outline-secondary"
                                onClick={this.handleReload}
                            >
                                Recargar página
                            </Button>
                        </div>
                    </div>
                </Container>
            );
        }

        return this.props.children;
    }
}

export default ErrorBoundary;
