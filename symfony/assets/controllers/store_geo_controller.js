import { Controller } from '@hotwired/stimulus'
import { Modal } from 'bootstrap'
import L from 'leaflet'

export default class extends Controller {
  static targets = ['map', 'flash']
  static values = {
    storeId: Number,
    buttonId: { type: String, default: 'btnStoreGeo' }
  }

  connect() {
    this.modal = new Modal(this.element)

    this.button = document.getElementById(this.buttonIdValue)
    if (this.button) {
      this._onClick = (e) => this.open(e)
      this.button.addEventListener('click', this._onClick)
    }
  }

  disconnect() {
    if (this.button && this._onClick) {
      this.button.removeEventListener('click', this._onClick)
    }
  }

  open(event) {
    event.preventDefault()
    this.modal.show()

    // Delay necesario para que el modal tenga tamaño
    setTimeout(() => this.initMap(), 200)
  }

  initMap() {
    if (this.mapInstance) return

    // Coordenadas por defecto (Buenos Aires)
    const lat = -34.6037
    const lng = -58.3816

    this.mapInstance = L.map(this.mapTarget).setView([lat, lng], 13)

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap'
    }).addTo(this.mapInstance)

    this.marker = L.marker([lat, lng], {
      draggable: true
    }).addTo(this.mapInstance)
  }

  save() {
    const { lat, lng } = this.marker.getLatLng()

    fetch(`/admin/store/${this.storeIdValue}/geo/ajax-save`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({ lat, lng })
    })
      .then(async r => {
        const data = await r.json()
        if (!r.ok || !data.success) throw new Error(data.message)
        this.showMessage(data.message, 'success')
      })
      .catch(err => this.showMessage(err.message, 'danger'))
  }

  showMessage(message, type) {
    if (!this.hasFlashTarget) return
    this.flashTarget.innerHTML = `
      <div class="alert alert-${type} alert-dismissible fade show">
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    `
  }
}
