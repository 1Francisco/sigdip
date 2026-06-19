export function mockCapacitorNetwork(online = true) {
  const status = { connected: online, connectionType: online ? 'wifi' : 'none' }
  cy.stub(globalThis, 'navigator').value({
    ...navigator,
    onLine: online,
  })
  return status
}

export function mockCapacitorModules(cy) {
  cy.window().then((win) => {
    if (!win) return;
    try {

    Object.defineProperty(win, 'Capacitor', {
      value: {
        isNativePlatform: () => false,
        platform: 'web',
      },
      writable: true,
      configurable: true,
    })

    win.CapacitorPlugins = {
      Network: {
        getStatus: () => Promise.resolve({ connected: true, connectionType: 'wifi' }),
        addListener: (event, cb) => {
          if (event === 'networkStatusChange') {
            win.__networkCallback = cb
          }
          return { remove: () => {} }
        },
        removeAllListeners: () => Promise.resolve(),
      },
      LocalNotifications: {
        checkPermissions: () => Promise.resolve({ display: 'granted' }),
        requestPermissions: () => Promise.resolve({ display: 'granted' }),
        schedule: () => Promise.resolve({ notifications: [] }),
        registerActionTypes: () => Promise.resolve(),
        cancel: () => Promise.resolve({ notifications: [] }),
      },
      Camera: {
        checkPermissions: () => Promise.resolve({ camera: 'granted' }),
        requestPermissions: () => Promise.resolve({ camera: 'granted' }),
        getPhoto: () => Promise.resolve({ base64String: '', path: '' }),
      },
      Geolocation: {
        getCurrentPosition: () => Promise.resolve({
          coords: { latitude: 19.0, longitude: -99.0, accuracy: 10 },
          timestamp: Date.now(),
        }),
      },
      Filesystem: {
        writeFile: () => Promise.resolve({ uri: 'file:///test' }),
        readFile: () => Promise.resolve({ data: '' }),
        deleteFile: () => Promise.resolve(),
      },
      Share: {
        share: () => Promise.resolve({ activityType: null }),
      },
      StatusBar: {
        setStyle: () => Promise.resolve(),
        setOverlaysWebView: () => Promise.resolve(),
      },
      Keyboard: {
        addListener: () => ({ remove: () => {} }),
        removeAllListeners: () => Promise.resolve(),
      },
      App: {
        addListener: () => ({ remove: () => {} }),
        removeAllListeners: () => Promise.resolve(),
        getInfo: () => Promise.resolve({ version: '1.0.0', build: '1' }),
      },
    }

    win.HTMLCanvasElement.prototype.getContext = function () {
      return {
        fillRect: () => {},
        clearRect: () => {},
        getImageData: () => ({ data: [] }),
        putImageData: () => {},
        createImageData: () => [],
        setTransform: () => {},
        drawImage: () => {},
        save: () => {},
        fillText: () => {},
        restore: () => {},
        beginPath: () => {},
        moveTo: () => {},
        lineTo: () => {},
        closePath: () => {},
        stroke: () => {},
        translate: () => {},
        scale: () => {},
        rotate: () => {},
        arc: () => {},
        fill: () => {},
        measureText: () => ({ width: 0 }),
        transform: () => {},
        rect: () => {},
        clip: () => {},
      }
    }

    win.CustomEvent = win.CustomEvent || (() => {})
    } catch (e) {
      // Silently fail - window might be undefined in headless mode during cleanup
    }
  })
}
