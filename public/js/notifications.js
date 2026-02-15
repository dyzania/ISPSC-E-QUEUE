if (typeof EQueueNotifications === "undefined") {
  class EQueueNotifications {
    constructor() {
      // Defensive check to avoid double initialization
      if (window.equeueNotifsInit) return;
      window.equeueNotifsInit = true;

      this.pollInterval = 10000; // 10 seconds
      this.isSupported = "Notification" in window;
      this.hasPermission = false;
      this.toastContainer = null;

      console.log(
        "%c🔔 E-Queue Notifications starting...",
        "color: #059669; font-weight: bold; font-size: 12px;",
      );

      // Support both new and old variable names during migration
      this.baseUrl =
        typeof ANTIGRAVITY_BASE_URL !== "undefined"
          ? ANTIGRAVITY_BASE_URL
          : typeof EQUEUE_BASE_URL !== "undefined"
            ? EQUEUE_BASE_URL
            : undefined;

      if (!this.baseUrl) {
        console.error(
          "Antigravity Error: ANTIGRAVITY_BASE_URL (or EQUEUE_BASE_URL) is missing. Notifications disabled.",
        );
        return;
      }

      this.injectStyles();
      this.listenForManualToasts();
      this.init();
    }

    injectStyles() {
      if (document.getElementById("equeue-notif-styles")) return;
      const style = document.createElement("style");
      style.id = "equeue-notif-styles";
      style.textContent = `
          #equeue-toast-container {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 2147483647; /* Use maximum possible z-index */
            display: flex;
            flex-direction: column;
            gap: 12px;
            pointer-events: none;
          }
          .equeue-toast {
            min-width: 320px;
            max-width: 450px;
            background: white;
            border-radius: 16px;
            padding: 16px 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            gap: 16px;
            pointer-events: auto;
            animation: toast-slide-in 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            cursor: pointer;
            overflow: hidden;
            position: relative;
            backdrop-filter: blur(8px);
          }
          .equeue-toast::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            height: 4px;
            background: currentColor;
            width: 100%;
            animation: toast-progress 3s linear forwards;
            opacity: 0.3;
          }
          .equeue-toast-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
          }
          .equeue-toast-content {
            flex: 1;
          }
          .equeue-toast-title {
            font-weight: 800;
            color: #111827;
            font-size: 13px;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
          }
          .equeue-toast-message {
            color: #4b5563;
            font-size: 14px;
            font-weight: 500;
            line-height: 1.4;
          }
          @keyframes toast-slide-in {
            from { transform: translateX(120%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
          }
          @keyframes toast-progress {
            from { width: 100%; }
            to { width: 0%; }
          }
          .equeue-toast.fade-out {
            animation: toast-fade-out 0.3s ease-in forwards;
          }
          @keyframes toast-fade-out {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(20px); opacity: 0; }
          }
        `;
      document.head.appendChild(style);
    }

    async init() {
      this.createToastContainer();

      if (this.isSupported) {
        if (Notification.permission === "granted") {
          this.hasPermission = true;
        } else if (Notification.permission !== "denied") {
          this.requestPermission();
        }
      }

      this.startPolling();
      console.log("E-Queue: Notifications active. Endpoint: " + this.baseUrl);
    }

    createToastContainer() {
      if (document.getElementById("equeue-toast-container")) {
        this.toastContainer = document.getElementById("equeue-toast-container");
        return;
      }
      this.toastContainer = document.createElement("div");
      this.toastContainer.id = "equeue-toast-container";
      document.body.appendChild(this.toastContainer);
    }

    async requestPermission() {
      try {
        const permission = await Notification.requestPermission();
        if (permission === "granted") {
          this.hasPermission = true;
          console.log("E-Queue: Native notifications granted.");
        }
      } catch (error) {
        console.warn("E-Queue: Native notification request failed.");
      }
    }

    startPolling() {
      this.checkNotifications();
      setInterval(() => this.checkNotifications(), this.pollInterval);
    }

    async checkNotifications() {
      if (document.hidden) return;
      try {
        const apiUrl = `${this.baseUrl}/api/get-notifications.php`;
        const response = await fetch(apiUrl);

        if (!response.ok) {
          if (response.status === 401) return; // User logged out
          throw new Error("API " + response.status);
        }

        const data = await response.json();

        if (
          data.success &&
          data.notifications &&
          data.notifications.length > 0
        ) {
          console.log("E-Queue: " + data.notifications.length + " new alerts.");
          data.notifications.forEach((notif) => {
            this.processNotification(notif);
          });
        }
      } catch (error) {
        // Periodic server errors or networking issues are ignored silently to avoid console spam
      }
    }

    processNotification(notif) {
      this.showToast(notif);

      if (this.hasPermission) {
        this.showBrowserNotification(notif);
      }

      this.playAlertSound();
      this.handleAutoReload(notif);
    }

    // Allow other scripts to trigger a toast manually
    listenForManualToasts() {
      document.addEventListener("equeue:toast", (e) => {
        const notif = {
          type: e.detail.type || "system",
          message: e.detail.message,
          title: e.detail.title,
        };

        this.showToast(notif);

        // Trigger native notification (ALL notifications if permitted)
        if (this.hasPermission) {
          this.showBrowserNotification(notif);
        }
      });
    }

    showToast(notif) {
      const toast = document.createElement("div");
      toast.className = "equeue-toast";

      let title = notif.title || "System Alert";
      let iconClass = "fa-bell";
      let colorClass = "text-indigo-600";
      let bgClass = "bg-indigo-50";

      if (notif.type === "turn_next") {
        title = notif.title || "IT IS YOUR TURN!";
        iconClass = "fa-bullhorn";
        colorClass = "text-emerald-600";
        bgClass = "bg-emerald-50";
      } else if (notif.type === "completed" || notif.type === "success") {
        title = notif.title || "Success";
        iconClass = "fa-check-circle";
        colorClass = "text-blue-600";
        bgClass = "bg-blue-50";
      } else if (notif.type === "now_serving" || notif.type === "serving") {
        title = notif.title || "Now Serving";
        iconClass = "fa-play-circle";
        colorClass = "text-amber-600";
        bgClass = "bg-amber-50";
      } else if (notif.type === "cancelled") {
        title = notif.title || "Ticket Cancelled";
        iconClass = "fa-times-circle";
        colorClass = "text-red-600";
        bgClass = "bg-red-50";
      }

      toast.style.color = colorClass.includes("text-emerald")
        ? "#059669"
        : colorClass.includes("text-blue")
          ? "#2563eb"
          : colorClass.includes("text-amber")
            ? "#d97706"
            : colorClass.includes("text-red")
              ? "#dc2626"
              : "#4f46e5";

      toast.innerHTML = `
          <div class="equeue-toast-icon ${bgClass} ${colorClass}">
            <i class="fas ${iconClass}"></i>
          </div>
          <div class="equeue-toast-content">
            <div class="equeue-toast-title">${title}</div>
            <div class="equeue-toast-message">${notif.message}</div>
          </div>
        `;

      toast.onclick = () => {
        window.focus();
        if (!window.location.href.includes("my-ticket.php")) {
          window.location.href = `${this.baseUrl}/user/my-ticket.php`;
        }
      };

      if (this.toastContainer) {
        this.toastContainer.appendChild(toast);
      }

      setTimeout(() => {
        toast.classList.add("fade-out");
        setTimeout(() => toast.remove(), 300);
      }, 3000);
    }

    showBrowserNotification(notif) {
      let title = notif.title || "E-Queue Alert";

      // Only override if no title provided and type matches specific cases
      if (!notif.title) {
        if (notif.type === "turn_next") title = "IT IS YOUR TURN!";
        else if (notif.type === "completed") title = "Service Completed";
      }

      try {
        const n = new Notification(title, {
          body: notif.message,
          tag: "ticket-" + (notif.ticket_id || "system"),
          renotify: true,
        });
        n.onclick = () => {
          window.focus();
          if (!window.location.href.includes("my-ticket.php")) {
            window.location.href = `${this.baseUrl}/user/my-ticket.php`;
          }
          n.close();
        };
      } catch (e) {}
    }

    handleAutoReload(notif) {
      if (
        [
          "completed",
          "turn_next",
          "now_serving",
          "serving",
          "cancelled",
        ].includes(notif.type)
      ) {
        const currentPath = window.location.pathname;
        if (
          currentPath.includes("dashboard.php") ||
          currentPath.includes("my-ticket.php") ||
          currentPath.includes("history.php")
        ) {
          // Wait for the 3s toast + 300ms fade-out before reloading
          const delay = 3500;
          setTimeout(() => window.location.reload(), delay);
        }
      }
    }

    playAlertSound() {
      try {
        const audioCtx = new (
          window.AudioContext || window.webkitAudioContext
        )();
        const oscillator = audioCtx.createOscillator();
        const gainNode = audioCtx.createGain();
        oscillator.type = "sine";
        oscillator.frequency.setValueAtTime(880, audioCtx.currentTime);
        gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);
        oscillator.connect(gainNode);
        gainNode.connect(audioCtx.destination);
        oscillator.start();
        oscillator.stop(audioCtx.currentTime + 0.2);
      } catch (e) {}
    }
  }

  class EQueueModal {
    constructor() {
      if (window.equeueModalInit) return;
      window.equeueModalInit = true;
      this.injectStyles();
      this.createContainer();
    }

    injectStyles() {
      if (document.getElementById("equeue-modal-styles")) return;
      const style = document.createElement("style");
      style.id = "equeue-modal-styles";
      style.textContent = `
        .equeue-modal-overlay {
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background: rgba(15, 23, 42, 0.4);
          backdrop-filter: blur(8px);
          -webkit-backdrop-filter: blur(8px);
          z-index: 2147483647;
          display: flex;
          align-items: center;
          justify-content: center;
          padding: 20px;
          opacity: 0;
          visibility: hidden;
          transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
          pointer-events: none;
        }
        .equeue-modal-overlay.active {
          opacity: 1;
          visibility: visible;
          pointer-events: auto;
        }
        .equeue-modal-card {
          background: white;
          width: 100%;
          max-width: 380px;
          border-radius: 28px;
          box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3);
          border: 1px solid rgba(255, 255, 255, 0.1);
          overflow: hidden;
          transform: scale(0.9) translateY(20px);
          transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .equeue-modal-overlay.active .equeue-modal-card {
          transform: scale(1) translateY(0);
        }
        @media (max-width: 640px) {
          .equeue-modal-overlay {
            align-items: flex-end;
            padding: 10px;
          }
          .equeue-modal-card {
            max-width: 100%;
            border-radius: 24px 24px 0 0;
            transform: translateY(100%);
            margin-bottom: -10px;
          }
          .equeue-modal-overlay.active .equeue-modal-card {
            transform: translateY(0);
          }
          .equeue-modal-header {
            padding: 24px 20px 8px;
          }
          .equeue-modal-body {
            padding: 0 20px 20px;
          }
          .equeue-modal-footer {
            padding: 16px 20px 28px;
          }
        }
        .equeue-modal-header {
          padding: 24px 24px 12px;
          text-align: center;
        }
        .equeue-modal-icon {
          width: 48px;
          height: 48px;
          border-radius: 14px;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 22px;
          margin: 0 auto 16px;
        }
        .equeue-modal-title {
          font-family: 'Outfit', sans-serif;
          font-size: 20px;
          font-weight: 900;
          color: #0f172a;
          letter-spacing: -0.02em;
          line-height: 1.2;
        }
        .equeue-modal-body {
          padding: 0 24px 24px;
          text-align: center;
        }
        .equeue-modal-message {
          font-size: 14px;
          color: #64748b;
          font-weight: 500;
          line-height: 1.5;
        }
        .equeue-modal-footer {
          padding: 20px 24px 24px;
          display: flex;
          gap: 10px;
          background: #f8fafc;
        }
        .equeue-modal-btn {
          flex: 1;
          padding: 12px;
          border-radius: 14px;
          font-weight: 800;
          font-size: 13px;
          text-transform: uppercase;
          letter-spacing: 0.05em;
          transition: all 0.2s ease;
          cursor: pointer;
          border: none;
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 8px;
        }
        .equeue-modal-btn-cancel {
          background: white;
          color: #64748b;
          border: 1px solid #e2e8f0;
        }
        .equeue-modal-btn-cancel:hover {
          background: #f1f5f9;
          color: #0f172a;
        }
        .equeue-modal-btn-confirm {
          background: #0f172a;
          color: white;
        }
        .equeue-modal-btn-confirm:hover {
          background: #000;
          transform: translateY(-2px);
          box-shadow: 0 10px 20px -5px rgba(15, 23, 42, 0.4);
        }
        .equeue-modal-icon.alert { background: #f0fdf2; color: #059669; }
        .equeue-modal-icon.confirm { background: #fff7ed; color: #ea580c; }
        .equeue-modal-icon.error { background: #fef2f2; color: #dc2626; }
      `;
      document.head.appendChild(style);
    }

    createContainer() {
      if (document.getElementById("equeue-modal-overlay")) return;
      const overlay = document.createElement("div");
      overlay.id = "equeue-modal-overlay";
      overlay.className = "equeue-modal-overlay";
      overlay.innerHTML = `
        <div class="equeue-modal-card">
          <div class="equeue-modal-header">
            <div id="equeue-modal-icon" class="equeue-modal-icon"></div>
            <h3 id="equeue-modal-title" class="equeue-modal-title"></h3>
          </div>
          <div class="equeue-modal-body">
            <p id="equeue-modal-message" class="equeue-modal-message"></p>
          </div>
          <div id="equeue-modal-footer" class="equeue-modal-footer"></div>
        </div>
      `;
      document.body.appendChild(overlay);
      this.overlay = overlay;
      this.iconEl = document.getElementById("equeue-modal-icon");
      this.titleEl = document.getElementById("equeue-modal-title");
      this.messageEl = document.getElementById("equeue-modal-message");
      this.footerEl = document.getElementById("equeue-modal-footer");
    }

    show(options = {}) {
      const {
        title = "Attention",
        message = "",
        type = "alert", // alert, confirm, error
        confirmText = type === "confirm" ? "Proceed" : "Got it",
        cancelText = "Cancel",
        onConfirm = null,
        onCancel = null,
      } = options;

      this.titleEl.textContent = title;
      this.messageEl.textContent = message;

      // Set Icon
      this.iconEl.className = `equeue-modal-icon ${type}`;
      let iconHtml = '<i class="fas fa-bell"></i>';
      if (type === "confirm")
        iconHtml = '<i class="fas fa-question-circle"></i>';
      if (type === "error")
        iconHtml = '<i class="fas fa-exclamation-triangle"></i>';
      if (type === "success") {
        this.iconEl.className = "equeue-modal-icon alert";
        iconHtml = '<i class="fas fa-check-circle"></i>';
      }
      this.iconEl.innerHTML = iconHtml;

      // Clear footer
      this.footerEl.innerHTML = "";

      // Add Cancel Button for Confirm type
      if (type === "confirm") {
        const cancelBtn = document.createElement("button");
        cancelBtn.className = "equeue-modal-btn equeue-modal-btn-cancel";
        cancelBtn.textContent = cancelText;
        cancelBtn.onclick = () => {
          this.hide();
          if (onCancel) onCancel();
        };
        this.footerEl.appendChild(cancelBtn);
      }

      // Add Confirm Button
      const confirmBtn = document.createElement("button");
      confirmBtn.className = "equeue-modal-btn equeue-modal-btn-confirm";
      confirmBtn.textContent = confirmText;
      confirmBtn.onclick = () => {
        this.hide();
        if (onConfirm) onConfirm();
      };
      this.footerEl.appendChild(confirmBtn);

      // Show overlay
      setTimeout(() => this.overlay.classList.add("active"), 10);
    }

    hide() {
      this.overlay.classList.remove("active");
    }
  }

  // Global helper functions to match native syntax but async
  window.equeueAlert = (message, title = "System Alert") => {
    return new Promise((resolve) => {
      if (!window.equeueModal) window.equeueModal = new EQueueModal();
      window.equeueModal.show({
        title,
        message,
        type: "alert",
        onConfirm: resolve,
      });
    });
  };

  window.equeueConfirm = (message, title = "Please Confirm") => {
    return new Promise((resolve) => {
      if (!window.equeueModal) window.equeueModal = new EQueueModal();
      window.equeueModal.show({
        title,
        message,
        type: "confirm",
        onConfirm: () => resolve(true),
        onCancel: () => resolve(false),
      });
    });
  };

  window.equeueSuccess = (message, title = "Success") => {
    return new Promise((resolve) => {
      if (!window.equeueModal) window.equeueModal = new EQueueModal();
      window.equeueModal.show({
        title,
        message,
        type: "success",
        onConfirm: resolve,
      });
    });
  };

  // Auto-instantiate
  window.EQueueNotifications = EQueueNotifications;
  window.EQueueModal = EQueueModal;

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => {
      new EQueueNotifications();
      window.equeueModal = new EQueueModal();
    });
  } else {
    new EQueueNotifications();
    window.equeueModal = new EQueueModal();
  }
}
