import React, { useState, useEffect } from 'react';
import {
  SafeAreaView,
  StyleSheet,
  View,
  Text,
  TouchableOpacity,
  FlatList,
  Alert,
  RefreshControl,
  ActivityIndicator,
  StatusBar,
} from 'react-native';
import Icon from 'react-native-vector-icons/MaterialCommunityIcons';
import AsyncStorage from '@react-native-async-storage/async-storage';
import DeviceInfo from 'react-native-device-info';
import PushNotification from 'react-native-push-notification';

// Configurar notificaciones locales
PushNotification.configure({
  onNotification: function (notification) {
    console.log('NOTIFICATION:', notification);
  },
  requestPermissions: Platform.OS === 'ios',
});

const API_URL = 'http://192.168.1.100:8080/api'; // Cambiar por tu IP local

type Device = {
  id: number;
  nombre: string;
  tipo: string;
  temperatura_actual: number;
  estado: string;
  ubicacion: string;
  uuid: string;
};

type User = {
  id: number;
  nombre: string;
  email: string;
  rol: string;
};

export default function App() {
  const [user, setUser] = useState<User | null>(null);
  const [devices, setDevices] = useState<Device[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [stats, setStats] = useState({
    total: 0,
    encendidos: 0,
    consumo_total: 0,
  });

  // Conectar WebSocket
  const [ws, setWs] = useState<WebSocket | null>(null);

  useEffect(() => {
    checkAuth();
    initWebSocket();
    
    return () => {
      if (ws) {
        ws.close();
      }
    };
  }, []);

  const initWebSocket = () => {
    const websocket = new WebSocket('ws://192.168.1.100:8082');
    
    websocket.onopen = () => {
      console.log('WebSocket connected');
      websocket.send(JSON.stringify({
        action: 'subscribe',
        channel: 'alerts'
      }));
    };
    
    websocket.onmessage = (event) => {
      const data = JSON.parse(event.data);
      handleWebSocketMessage(data);
    };
    
    websocket.onerror = (error) => {
      console.log('WebSocket error:', error);
    };
    
    websocket.onclose = () => {
      console.log('WebSocket disconnected');
    };
    
    setWs(websocket);
  };

  const handleWebSocketMessage = (data: any) => {
    switch (data.type) {
      case 'temperature_update':
        updateDeviceTemperature(data.device_id, data.temperature);
        break;
        
      case 'alert':
        showAlertNotification(data);
        break;
        
      case 'device_status':
        updateDeviceStatus(data.device_id, data.status);
        break;
    }
  };

  const checkAuth = async () => {
    try {
      const token = await AsyncStorage.getItem('cooling_token');
      if (token) {
        fetchUserData(token);
      } else {
        setLoading(false);
      }
    } catch (error) {
      console.error('Auth check error:', error);
      setLoading(false);
    }
  };

  const fetchUserData = async (token: string) => {
    try {
      const response = await fetch(`${API_URL}/auth/me`, {
        headers: {
          'Authorization': `Bearer ${token}`,
        },
      });
      
      if (response.ok) {
        const data = await response.json();
        setUser(data.data);
        fetchDevices(token);
      } else {
        await AsyncStorage.removeItem('cooling_token');
        setLoading(false);
      }
    } catch (error) {
      console.error('Fetch user error:', error);
      setLoading(false);
    }
  };

  const fetchDevices = async (token: string) => {
    try {
      const response = await fetch(`${API_URL}/dispositivos`, {
        headers: {
          'Authorization': `Bearer ${token}`,
        },
      });
      
      if (response.ok) {
        const data = await response.json();
        setDevices(data.data);
        setStats(data.estadisticas);
      }
    } catch (error) {
      console.error('Fetch devices error:', error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  const onRefresh = async () => {
    setRefreshing(true);
    const token = await AsyncStorage.getItem('cooling_token');
    if (token) {
      fetchDevices(token);
    }
  };

  const login = async (email: string, password: string) => {
    try {
      const response = await fetch(`${API_URL}/auth/login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email, password }),
      });
      
      const data = await response.json();
      
      if (response.ok) {
        await AsyncStorage.setItem('cooling_token', data.data.token);
        setUser(data.data.usuario);
        fetchDevices(data.data.token);
        return true;
      } else {
        Alert.alert('Error', data.message || 'Error en el login');
        return false;
      }
    } catch (error) {
      Alert.alert('Error', 'Error de conexión');
      return false;
    }
  };

  const logout = async () => {
    const token = await AsyncStorage.getItem('cooling_token');
    if (token) {
      await fetch(`${API_URL}/auth/logout`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
        },
      });
    }
    
    await AsyncStorage.removeItem('cooling_token');
    setUser(null);
    setDevices([]);
  };

  const toggleDeviceStatus = async (deviceId: number, currentStatus: string) => {
    const token = await AsyncStorage.getItem('cooling_token');
    if (!token) return;
    
    const action = currentStatus === 'encendido' ? 'apagar' : 'encender';
    
    try {
      const response = await fetch(`${API_URL}/dispositivos/${deviceId}/${action}`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
        },
      });
      
      if (response.ok) {
        onRefresh();
      }
    } catch (error) {
      console.error('Toggle device error:', error);
    }
  };

  const updateDeviceTemperature = (deviceId: number, temperature: number) => {
    setDevices(prevDevices =>
      prevDevices.map(device =>
        device.id === deviceId
          ? { ...device, temperatura_actual: temperature }
          : device
      )
    );
  };

  const updateDeviceStatus = (deviceId: number, status: string) => {
    setDevices(prevDevices =>
      prevDevices.map(device =>
        device.id === deviceId
          ? { ...device, estado: status }
          : device
      )
    );
  };

  const showAlertNotification = (alert: any) => {
    PushNotification.localNotification({
      title: alert.message,
      message: `Dispositivo: ${alert.device_name}`,
      playSound: true,
      soundName: 'default',
      importance: 'high',
      priority: 'high',
    });
    
    Alert.alert(
      '🚨 Alerta del Sistema',
      `${alert.message}\n\nDispositivo: ${alert.device_name}\nTemperatura: ${alert.temperature}°C`,
      [{ text: 'OK' }]
    );
  };

  const getDeviceIcon = (tipo: string) => {
    switch (tipo) {
      case 'aire_acondicionado':
        return 'air-conditioner';
      case 'refrigerador':
        return 'fridge';
      case 'ventilador':
        return 'fan';
      default:
        return 'thermometer';
    }
  };

  const getStatusColor = (estado: string) => {
    switch (estado) {
      case 'encendido':
        return '#4CAF50';
      case 'apagado':
        return '#F44336';
      case 'mantenimiento':
        return '#FF9800';
      default:
        return '#9E9E9E';
    }
  };

  const LoginScreen = () => (
    <View style={styles.loginContainer}>
      <View style={styles.loginHeader}>
        <Icon name="snowflake" size={80} color="#4facfe" />
        <Text style={styles.loginTitle}>Cooling System</Text>
        <Text style={styles.loginSubtitle}>Control de temperatura inteligente</Text>
      </View>
      
      <TouchableOpacity
        style={styles.loginButton}
        onPress={() => login('demo@cooling.com', 'demo123')}
      >
        <Text style={styles.loginButtonText}>Iniciar con cuenta demo</Text>
      </TouchableOpacity>
      
      <Text style={styles.versionText}>
        v{DeviceInfo.getVersion()} - {DeviceInfo.getBuildNumber()}
      </Text>
    </View>
  );

  const DeviceCard = ({ item }: { item: Device }) => (
    <View style={styles.deviceCard}>
      <View style={styles.deviceHeader}>
        <Icon name={getDeviceIcon(item.tipo)} size={24} color="#333" />
        <View style={styles.deviceInfo}>
          <Text style={styles.deviceName}>{item.nombre}</Text>
          <Text style={styles.deviceLocation}>{item.ubicacion || 'Sin ubicación'}</Text>
        </View>
        <View style={[styles.statusBadge, { backgroundColor: getStatusColor(item.estado) }]}>
          <Text style={styles.statusText}>{item.estado.toUpperCase()}</Text>
        </View>
      </View>
      
      <View style={styles.deviceBody}>
        <View style={styles.temperatureContainer}>
          <Icon name="thermometer" size={20} color="#FF6B6B" />
          <Text style={styles.temperatureText}>
            {item.temperatura_actual ? `${item.temperatura_actual}°C` : '--°C'}
          </Text>
        </View>
        
        <TouchableOpacity
          style={[
            styles.toggleButton,
            { backgroundColor: item.estado === 'encendido' ? '#F44336' : '#4CAF50' }
          ]}
          onPress={() => toggleDeviceStatus(item.id, item.estado)}
        >
          <Icon
            name={item.estado === 'encendido' ? 'power' : 'power'}
            size={20}
            color="white"
          />
          <Text style={styles.toggleButtonText}>
            {item.estado === 'encendido' ? 'APAGAR' : 'ENCENDER'}
          </Text>
        </TouchableOpacity>
      </View>
      
      <View style={styles.deviceFooter}>
        <Text style={styles.deviceId}>ID: {item.uuid.substring(0, 8)}...</Text>
        <Icon name="chevron-right" size={20} color="#999" />
      </View>
    </View>
  );

  const StatsCard = ({ title, value, icon, color }: any) => (
    <View style={styles.statCard}>
      <View style={[styles.statIcon, { backgroundColor: color }]}>
        <Icon name={icon} size={24} color="white" />
      </View>
      <Text style={styles.statValue}>{value}</Text>
      <Text style={styles.statTitle}>{title}</Text>
    </View>
  );

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#4facfe" />
        <Text style={styles.loadingText}>Cargando Cooling System...</Text>
      </View>
    );
  }

  if (!user) {
    return (
      <SafeAreaView style={styles.container}>
        <StatusBar backgroundColor="#4facfe" barStyle="light-content" />
        <LoginScreen />
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar backgroundColor="#4facfe" barStyle="light-content" />
      
      {/* Header */}
      <View style={styles.header}>
        <View>
          <Text style={styles.welcomeText}>Hola, {user.nombre}</Text>
          <Text style={styles.emailText}>{user.email}</Text>
        </View>
        <TouchableOpacity onPress={logout} style={styles.logoutButton}>
          <Icon name="logout" size={24} color="#FFF" />
        </TouchableOpacity>
      </View>
      
      {/* Stats */}
      <View style={styles.statsContainer}>
        <StatsCard
          title="Dispositivos"
          value={stats.total}
          icon="devices"
          color="#4facfe"
        />
        <StatsCard
          title="Encendidos"
          value={stats.encendidos}
          icon="power"
          color="#4CAF50"
        />
        <StatsCard
          title="Consumo"
          value={`${stats.consumo_total}kWh`}
          icon="lightning-bolt"
          color="#FF9800"
        />
      </View>
      
      {/* Devices List */}
      <View style={styles.devicesContainer}>
        <View style={styles.sectionHeader}>
          <Text style={styles.sectionTitle}>Mis Dispositivos</Text>
          <TouchableOpacity onPress={onRefresh}>
            <Icon name="refresh" size={24} color="#4facfe" />
          </TouchableOpacity>
        </View>
        
        <FlatList
          data={devices}
          renderItem={DeviceCard}
          keyExtractor={(item) => item.id.toString()}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={onRefresh}
              colors={['#4facfe']}
            />
          }
          ListEmptyComponent={
            <View style={styles.emptyContainer}>
              <Icon name="devices-off" size={60} color="#CCC" />
              <Text style={styles.emptyText}>No hay dispositivos</Text>
              <Text style={styles.emptySubtext}>Agrega tu primer dispositivo</Text>
            </View>
          }
        />
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F5F5F5',
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#FFF',
  },
  loadingText: {
    marginTop: 20,
    fontSize: 16,
    color: '#666',
  },
  loginContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
    backgroundColor: '#FFF',
  },
  loginHeader: {
    alignItems: 'center',
    marginBottom: 50,
  },
  loginTitle: {
    fontSize: 32,
    fontWeight: 'bold',
    color: '#333',
    marginTop: 20,
  },
  loginSubtitle: {
    fontSize: 16,
    color: '#666',
    marginTop: 5,
  },
  loginButton: {
    backgroundColor: '#4facfe',
    paddingVertical: 15,
    paddingHorizontal: 40,
    borderRadius: 10,
    marginTop: 30,
  },
  loginButtonText: {
    color: '#FFF',
    fontSize: 18,
    fontWeight: 'bold',
  },
  versionText: {
    position: 'absolute',
    bottom: 30,
    color: '#999',
    fontSize: 12,
  },
  header: {
    backgroundColor: '#4facfe',
    padding: 20,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  welcomeText: {
    color: '#FFF',
    fontSize: 20,
    fontWeight: 'bold',
  },
  emailText: {
    color: 'rgba(255,255,255,0.8)',
    fontSize: 14,
    marginTop: 2,
  },
  logoutButton: {
    padding: 10,
  },
  statsContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    padding: 20,
    backgroundColor: '#FFF',
    marginBottom: 10,
  },
  statCard: {
    alignItems: 'center',
    flex: 1,
  },
  statIcon: {
    width: 50,
    height: 50,
    borderRadius: 25,
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 10,
  },
  statValue: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#333',
  },
  statTitle: {
    fontSize: 12,
    color: '#666',
    marginTop: 5,
  },
  devicesContainer: {
    flex: 1,
    backgroundColor: '#FFF',
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    paddingTop: 20,
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    marginBottom: 15,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#333',
  },
  deviceCard: {
    backgroundColor: '#FFF',
    borderRadius: 10,
    marginHorizontal: 20,
    marginBottom: 15,
    padding: 15,
    borderWidth: 1,
    borderColor: '#EEE',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  deviceHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 15,
  },
  deviceInfo: {
    flex: 1,
    marginLeft: 10,
  },
  deviceName: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#333',
  },
  deviceLocation: {
    fontSize: 12,
    color: '#666',
    marginTop: 2,
  },
  statusBadge: {
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 15,
  },
  statusText: {
    color: '#FFF',
    fontSize: 10,
    fontWeight: 'bold',
  },
  deviceBody: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 15,
  },
  temperatureContainer: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  temperatureText: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#FF6B6B',
    marginLeft: 10,
  },
  toggleButton: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingVertical: 10,
    borderRadius: 20,
  },
  toggleButtonText: {
    color: '#FFF',
    fontWeight: 'bold',
    marginLeft: 5,
  },
  deviceFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderTopWidth: 1,
    borderTopColor: '#EEE',
    paddingTop: 10,
  },
  deviceId: {
    fontSize: 10,
    color: '#999',
  },
  emptyContainer: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 50,
  },
  emptyText: {
    fontSize: 18,
    color: '#666',
    marginTop: 20,
  },
  emptySubtext: {
    fontSize: 14,
    color: '#999',
    marginTop: 5,
  },
});