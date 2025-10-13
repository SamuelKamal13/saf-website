// Admin Panel JavaScript
const API_BASE_URL = "http://localhost:8080/dashboard/attendance_app/api";
let authToken = localStorage.getItem("admin_token");
let adminUser = JSON.parse(localStorage.getItem("admin_user") || "null");

// Initialize on page load
document.addEventListener("DOMContentLoaded", function () {
  // Check authentication
  if (!authToken) {
    // No token found, redirect to login
    window.location.href = "login.html";
    return;
  }

  // Verify token is still valid
  verifyAuthentication();

  // Display admin name
  if (adminUser) {
    document.getElementById("adminName").textContent = adminUser.name;
  }

  loadDashboard();
});

// Verify authentication with server
async function verifyAuthentication() {
  try {
    const response = await fetch(API_BASE_URL + "/get_user.php", {
      headers: {
        Authorization: `Bearer ${authToken}`,
      },
    });

    const result = await response.json();

    if (!result.success) {
      // Token is invalid or expired
      logoutUser();
    }
  } catch (error) {
    console.error("Authentication verification error:", error);
  }
}

// Logout user
function logoutUser() {
  localStorage.removeItem("admin_token");
  localStorage.removeItem("admin_user");
  localStorage.removeItem("token_expires_at");
  localStorage.removeItem("remember_me");
  window.location.href = "login.html";
}

// Tab switching
function switchTab(tabName) {
  // Hide all tabs
  document.querySelectorAll(".tab-content").forEach((tab) => {
    tab.classList.add("d-none");
  });

  // Remove active class from all nav tabs
  document.querySelectorAll(".nav-tab").forEach((navTab) => {
    navTab.classList.remove("active");
  });

  // Show selected tab
  document.getElementById(tabName + "Tab").classList.remove("d-none");

  // Add active class to clicked nav tab
  event.target.classList.add("active");

  // Load data for the tab
  switch (tabName) {
    case "dashboard":
      loadDashboard();
      break;
    case "users":
      loadUsers();
      break;
    case "events":
      loadEvents();
      break;
    case "barcodes":
      loadSharedBarcodes();
      break;
    case "attendance":
      loadAttendance();
      break;
    case "announcements":
      loadAnnouncements();
      break;
    case "reflections":
      loadReflections();
      break;
    case "categories":
      loadCategories();
      break;
    case "notifications":
      loadNotifications();
      break;
  }
}

// Show alert message
function showAlert(message, type = "success") {
  const alertContainer = document.getElementById("alertContainer");
  const alert = document.createElement("div");
  alert.className = `alert alert-${type}`;
  alert.textContent = message;
  alertContainer.appendChild(alert);

  setTimeout(() => {
    alert.remove();
  }, 5000);
}

// API Request Helper
async function apiRequest(endpoint, method = "GET", data = null) {
  const options = {
    method: method,
    headers: {
      "Content-Type": "application/json",
      Authorization: `Bearer ${authToken}`,
    },
  };

  if (data && method !== "GET") {
    options.body = JSON.stringify(data);
  }

  try {
    const response = await fetch(API_BASE_URL + endpoint, options);
    const result = await response.json();

    if (!result.success) {
      // Check if authentication error
      if (response.status === 401 || result.message.includes("token")) {
        showAlert("انتهت صلاحية الجلسة. جاري تسجيل الخروج...", "danger");
        setTimeout(() => logoutUser(), 2000);
      }
      throw new Error(result.message || "Request failed");
    }

    return result;
  } catch (error) {
    console.error("API Error:", error);
    showAlert(error.message, "danger");
    throw error;
  }
}

// Dashboard Functions
async function loadDashboard() {
  try {
    // Load statistics
    const users = await apiRequest("/get_user.php", "GET");
    const events = await apiRequest("/get_events.php");
    const attendance = await apiRequest("/get_attendance.php");
    const announcements = await apiRequest("/get_announcements.php");

    document.getElementById("totalUsers").textContent =
      users.data?.users?.length || 0;
    document.getElementById("totalEvents").textContent =
      events.data?.events?.length || 0;
    document.getElementById("totalAttendance").textContent =
      attendance.data?.attendances?.length ||
      attendance.data?.attendance?.length ||
      0;
    document.getElementById("totalAnnouncements").textContent =
      announcements.data?.announcements?.length || 0;

    // Load recent activity
    loadRecentActivity();
  } catch (error) {
    console.error("Dashboard load error:", error);
  }
}

async function loadRecentActivity() {
  const activityContainer = document.getElementById("recentActivity");

  try {
    // Fetch recent data
    const [attendanceResult, announcementsResult, eventsResult] =
      await Promise.all([
        apiRequest("/get_attendance.php").catch(() => ({ data: {} })),
        apiRequest("/get_announcements.php").catch(() => ({ data: {} })),
        apiRequest("/get_events.php").catch(() => ({ data: {} })),
      ]);

    const recentAttendance = (
      attendanceResult.data.attendances ||
      attendanceResult.data.attendance ||
      []
    ).slice(-5);
    const recentAnnouncements = (
      announcementsResult.data.announcements || []
    ).slice(-3);
    const recentEvents = (eventsResult.data.events || []).slice(-3);

    // Combine and sort activities
    let activities = [];

    recentAttendance.forEach((record) => {
      activities.push({
        icon: "✓",
        type: "Attendance",
        description: `${record.user_name} marked ${record.status} for ${record.event_name}`,
        date: new Date(record.timestamp),
        color: "#4caf50",
      });
    });

    recentAnnouncements.forEach((ann) => {
      activities.push({
        icon: "📢",
        type: "Announcement",
        description: `New announcement: ${ann.title}`,
        date: new Date(ann.date),
        color: "#D4AF37",
      });
    });

    recentEvents.forEach((event) => {
      activities.push({
        icon: "📅",
        type: "Event",
        description: `Event created: ${event.name}`,
        date: new Date(event.date),
        color: "#8B0000",
      });
    });

    // Sort by date (newest first)
    activities.sort((a, b) => b.date - a.date);
    activities = activities.slice(0, 10); // Show latest 10 activities

    if (activities.length === 0) {
      activityContainer.innerHTML = `
                <p class="text-center" style="padding: 20px; color: #666;">
                    No recent activity yet. Start by adding events, announcements, or tracking attendance!
                </p>
            `;
      return;
    }

    const activityHTML = `
            <div style="max-height: 400px; overflow-y: auto;">
                ${activities
                  .map(
                    (activity) => `
                    <div style="display: flex; gap: 15px; padding: 15px; border-bottom: 1px solid #f0f0f0; align-items: start;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background-color: ${
                          activity.color
                        }; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                            ${activity.icon}
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 600; color: ${
                              activity.color
                            }; margin-bottom: 4px;">
                                ${activity.type}
                            </div>
                            <div style="color: #333; margin-bottom: 4px;">
                                ${activity.description}
                            </div>
                            <div style="color: #999; font-size: 12px;">
                                ${activity.date.toLocaleString()}
                            </div>
                        </div>
                    </div>
                `
                  )
                  .join("")}
            </div>
        `;

    activityContainer.innerHTML = activityHTML;
  } catch (error) {
    console.error("Load recent activity error:", error);
    activityContainer.innerHTML = `
            <p class="text-center" style="padding: 20px; color: #666;">
                Failed to load recent activity
            </p>
        `;
  }
}

// Events Functions
async function loadEvents() {
  try {
    const result = await apiRequest("/get_events.php");
    const events = result.data.events || [];

    const tableHTML = `
            <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Barcode</th>
                        <th>Location</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${events
                      .map(
                        (event) => `
                        <tr>
                            <td>${event.id}</td>
                            <td>${event.name}</td>
                            <td><span class="badge badge-info">${
                              event.type
                            }</span></td>
                            <td>${new Date(event.date).toLocaleString()}</td>
                            <td><code>${event.barcode}</code></td>
                            <td>${event.location || "-"}</td>
                            <td>
                                <button class="btn btn-warning btn-sm" onclick="editEvent(${
                                  event.id
                                })">Edit</button>
                                <button class="btn btn-danger btn-sm" onclick="deleteEvent(${
                                  event.id
                                })">Delete</button>
                                <button class="btn btn-success btn-sm" onclick="generateQRCode(${
                                  event.id
                                })" title="Generate QR Code">📱</button>
                            </td>
                        </tr>
                    `
                      )
                      .join("")}
                </tbody>
            </table>
            </div>
        `;

    document.getElementById("eventsTableContainer").innerHTML = tableHTML;
  } catch (error) {
    document.getElementById("eventsTableContainer").innerHTML =
      '<p class="text-center">Failed to load events</p>';
  }
}

let editingEventId = null;

function showAddEventModal() {
  editingEventId = null;
  document.getElementById("eventForm").reset();
  document.querySelector("#eventModal .modal-header h2").textContent =
    "Add New Event";
  document.getElementById("eventModal").classList.add("active");
}

function editEvent(eventId) {
  editingEventId = eventId;
  // Fetch event details and populate form
  apiRequest("/get_events.php", "GET")
    .then((result) => {
      const event = result.data.events.find((e) => e.id == eventId);
      if (event) {
        document.querySelector('input[name="name"]').value = event.name;
        document.querySelector('select[name="type"]').value = event.type;
        document.querySelector('input[name="date"]').value = event.date
          .replace(" ", "T")
          .substring(0, 16);
        document.querySelector('input[name="barcode"]').value = event.barcode;
        document.querySelector('textarea[name="description"]').value =
          event.description || "";
        document.querySelector('input[name="location"]').value =
          event.location || "";
        document.querySelector("#eventModal .modal-header h2").textContent =
          "Edit Event";
        document.getElementById("eventModal").classList.add("active");
      }
    })
    .catch((error) => {
      showAlert("Failed to load event details", "danger");
      console.error("Load event error:", error);
    });
}

async function saveEvent(event) {
  event.preventDefault();
  const formData = new FormData(event.target);
  const data = {
    name: formData.get("name"),
    type: formData.get("type"),
    date: formData.get("date"),
    barcode: formData.get("barcode"),
    description: formData.get("description"),
    location: formData.get("location"),
  };

  try {
    const endpoint = editingEventId
      ? `/update_event.php?id=${editingEventId}`
      : "/add_event.php";
    const method = editingEventId ? "PUT" : "POST";

    await apiRequest(endpoint, method, data);
    showAlert(
      editingEventId
        ? "Event updated successfully!"
        : "Event added successfully!",
      "success"
    );
    closeModal("eventModal");
    event.target.reset();
    editingEventId = null;
    loadEvents();
  } catch (error) {
    console.error("Save event error:", error);
    showAlert("Failed to save event. Please try again.", "danger");
  }
}

async function deleteEvent(eventId) {
  if (!confirm("هل أنت متأكد من حذف هذه الفعالية؟")) {
    return;
  }

  try {
    await apiRequest(`/delete_event.php?id=${eventId}`, "DELETE");
    showAlert("تم حذف الفعالية بنجاح!", "success");
    loadEvents();
  } catch (error) {
    console.error("Delete event error:", error);
    showAlert("فشل حذف الفعالية. حاول مرة أخرى.", "danger");
  }
}

function generateQRCode(eventId) {
  // Get event details
  apiRequest("/get_events.php", "GET")
    .then((result) => {
      const event = result.data.events.find((e) => e.id == eventId);
      if (event) {
        // Open QR code generator service
        const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(
          event.barcode
        )}`;
        window.open(qrUrl, "_blank");
      }
    })
    .catch((error) => {
      showAlert("Failed to generate QR code", "danger");
      console.error("QR code error:", error);
    });
}

// Attendance Functions
async function loadAttendance() {
  try {
    const result = await apiRequest("/get_attendance.php");
    const attendance = result.data.attendances || result.data.attendance || [];

    const tableHTML = `
            <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Event</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    ${attendance
                      .map(
                        (record) => `
                        <tr>
                            <td>${record.id}</td>
                            <td>${record.user_name}</td>
                            <td>${record.event_name}</td>
                            <td>
                                <span class="badge badge-${
                                  record.status === "present"
                                    ? "success"
                                    : record.status === "excused"
                                    ? "warning"
                                    : "danger"
                                }">${record.status}</span>
                            </td>
                            <td>${new Date(
                              record.timestamp
                            ).toLocaleString()}</td>
                        </tr>
                    `
                      )
                      .join("")}
                </tbody>
            </table>
            </div>
        `;

    document.getElementById("attendanceTableContainer").innerHTML = tableHTML;
  } catch (error) {
    document.getElementById("attendanceTableContainer").innerHTML =
      '<p class="text-center">Failed to load attendance</p>';
  }
}

async function exportAttendance() {
  try {
    // Fetch all attendance data
    const result = await apiRequest("/get_attendance.php");
    const attendance = result.data.attendances || result.data.attendance || [];

    if (attendance.length === 0) {
      showAlert("No attendance data to export", "warning");
      return;
    }

    // Create CSV content
    const headers = ["ID", "User", "Event", "Status", "Date"];
    let csvContent = headers.join(",") + "\n";

    attendance.forEach((record) => {
      const row = [
        record.id,
        `"${record.user_name}"`,
        `"${record.event_name}"`,
        record.status,
        new Date(record.timestamp).toLocaleString(),
      ];
      csvContent += row.join(",") + "\n";
    });

    // Create blob and download
    const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);

    link.setAttribute("href", url);
    link.setAttribute(
      "download",
      `attendance_export_${new Date().toISOString().split("T")[0]}.csv`
    );
    link.style.visibility = "hidden";

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    showAlert("Attendance exported successfully!", "success");
  } catch (error) {
    console.error("Export attendance error:", error);
    showAlert("Failed to export attendance. Please try again.", "danger");
  }
}

async function exportAttendanceFiltered(startDate, endDate, eventType, status) {
  try {
    // Fetch all attendance data
    const result = await apiRequest("/get_attendance.php");
    let attendance = result.data.attendance || [];

    // Apply filters
    if (startDate) {
      attendance = attendance.filter(
        (record) => new Date(record.timestamp) >= new Date(startDate)
      );
    }
    if (endDate) {
      attendance = attendance.filter(
        (record) => new Date(record.timestamp) <= new Date(endDate)
      );
    }
    if (eventType && eventType !== "all") {
      attendance = attendance.filter(
        (record) => record.event_type === eventType
      );
    }
    if (status && status !== "all") {
      attendance = attendance.filter((record) => record.status === status);
    }

    if (attendance.length === 0) {
      showAlert("No attendance data matches the filters", "warning");
      return;
    }

    // Create CSV content
    const headers = ["ID", "User", "Event", "Status", "Date"];
    let csvContent = headers.join(",") + "\n";

    attendance.forEach((record) => {
      const row = [
        record.id,
        `"${record.user_name}"`,
        `"${record.event_name}"`,
        record.status,
        new Date(record.timestamp).toLocaleString(),
      ];
      csvContent += row.join(",") + "\n";
    });

    // Create blob and download
    const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);

    link.setAttribute("href", url);
    link.setAttribute(
      "download",
      `attendance_filtered_${new Date().toISOString().split("T")[0]}.csv`
    );
    link.style.visibility = "hidden";

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    showAlert("Filtered attendance exported successfully!", "success");
  } catch (error) {
    console.error("Export filtered attendance error:", error);
    showAlert("Failed to export attendance. Please try again.", "danger");
  }
}

// Announcements Functions
async function loadAnnouncements() {
  try {
    const result = await apiRequest("/get_announcements.php");
    const announcements = result.data.announcements || [];

    const tableHTML = `
            <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Content Preview</th>
                        <th>Author</th>
                        <th>Pinned</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${announcements
                      .map(
                        (ann) => `
                        <tr>
                            <td>${ann.id}</td>
                            <td>${ann.title}</td>
                            <td>${ann.content.substring(0, 50)}...</td>
                            <td>${ann.author}</td>
                            <td>${ann.is_pinned ? "📌" : "-"}</td>
                            <td>${new Date(ann.date).toLocaleDateString()}</td>
                            <td>
                                <button class="btn btn-warning btn-sm" onclick="editAnnouncement(${
                                  ann.id
                                })">Edit</button>
                                <button class="btn btn-danger btn-sm" onclick="deleteAnnouncement(${
                                  ann.id
                                })">Delete</button>
                                <button class="btn btn-${
                                  ann.is_pinned == 1 ? "danger" : "success"
                                } btn-sm" onclick="toggleAnnouncementPin(${
                          ann.id
                        })" title="${
                          ann.is_pinned == 1 ? "Unpin" : "Pin"
                        }">📌</button>
                            </td>
                        </tr>
                    `
                      )
                      .join("")}
                </tbody>
            </table>
            </div>
        `;

    document.getElementById("announcementsTableContainer").innerHTML =
      tableHTML;
  } catch (error) {
    document.getElementById("announcementsTableContainer").innerHTML =
      '<p class="text-center">Failed to load announcements</p>';
  }
}

let editingAnnouncementId = null;

function showAddAnnouncementModal() {
  editingAnnouncementId = null;
  document.getElementById("announcementForm").reset();
  document.querySelector("#announcementModal .modal-header h2").textContent =
    "Add New Announcement";
  document.getElementById("announcementModal").classList.add("active");
}

function editAnnouncement(announcementId) {
  editingAnnouncementId = announcementId;
  apiRequest("/get_announcements.php", "GET")
    .then((result) => {
      const announcement = result.data.announcements.find(
        (a) => a.id == announcementId
      );
      if (announcement) {
        document.querySelector('#announcementForm input[name="title"]').value =
          announcement.title;
        document.querySelector(
          '#announcementForm textarea[name="content"]'
        ).value = announcement.content;
        document.querySelector(
          '#announcementForm input[name="is_pinned"]'
        ).checked = announcement.is_pinned == 1;
        document.querySelector(
          "#announcementModal .modal-header h2"
        ).textContent = "Edit Announcement";
        document.getElementById("announcementModal").classList.add("active");
      }
    })
    .catch((error) => {
      showAlert("Failed to load announcement details", "danger");
      console.error("Load announcement error:", error);
    });
}

async function saveAnnouncement(event) {
  event.preventDefault();
  const formData = new FormData(event.target);
  const data = {
    title: formData.get("title"),
    content: formData.get("content"),
    is_pinned: formData.get("is_pinned") ? 1 : 0,
  };

  try {
    const endpoint = editingAnnouncementId
      ? `/update_announcement.php?id=${editingAnnouncementId}`
      : "/add_announcement.php";
    const method = "POST";

    await apiRequest(endpoint, method, data);
    showAlert(
      editingAnnouncementId
        ? "Announcement updated successfully!"
        : "Announcement added successfully!",
      "success"
    );
    closeModal("announcementModal");
    event.target.reset();
    editingAnnouncementId = null;
    loadAnnouncements();
  } catch (error) {
    console.error("Save announcement error:", error);
    showAlert("Failed to save announcement. Please try again.", "danger");
  }
}

async function deleteAnnouncement(announcementId) {
  if (!confirm("هل أنت متأكد من حذف هذا الإعلان؟")) {
    return;
  }

  try {
    await apiRequest(`/delete_announcement.php?id=${announcementId}`, "DELETE");
    showAlert("تم حذف الإعلان بنجاح!", "success");
    loadAnnouncements();
  } catch (error) {
    console.error("Delete announcement error:", error);
    showAlert("فشل حذف الإعلان. حاول مرة أخرى.", "danger");
  }
}

async function toggleAnnouncementPin(announcementId) {
  try {
    await apiRequest(
      `/toggle_announcement_pin.php?id=${announcementId}`,
      "POST"
    );
    showAlert("Announcement pin status updated!", "success");
    loadAnnouncements();
  } catch (error) {
    console.error("Toggle pin error:", error);
    showAlert("Failed to update pin status. Please try again.", "danger");
  }
}

// Reflections Functions
async function loadReflections() {
  try {
    const result = await apiRequest("/get_reflections.php");
    const reflections = result.data.reflections || [];

    const tableHTML = `
            <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Author</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${reflections
                      .map(
                        (ref) => `
                        <tr>
                            <td>${ref.id}</td>
                            <td>${ref.title}</td>
                            <td><span class="badge badge-info">${
                              ref.category
                            }</span></td>
                            <td>${ref.author}</td>
                            <td>${new Date(ref.date).toLocaleDateString()}</td>
                            <td>
                                <button class="btn btn-warning btn-sm" onclick="editReflection(${
                                  ref.id
                                })">Edit</button>
                                <button class="btn btn-danger btn-sm" onclick="deleteReflection(${
                                  ref.id
                                })">Delete</button>
                            </td>
                        </tr>
                    `
                      )
                      .join("")}
                </tbody>
            </table>
            </div>
        `;

    document.getElementById("reflectionsTableContainer").innerHTML = tableHTML;
  } catch (error) {
    document.getElementById("reflectionsTableContainer").innerHTML =
      '<p class="text-center">Failed to load reflections</p>';
  }
}

let editingReflectionId = null;

function showAddReflectionModal() {
  editingReflectionId = null;
  document.getElementById("reflectionForm").reset();
  document.getElementById("imagePreview").style.display = "none";
  document.querySelector("#reflectionModal .modal-header h2").textContent =
    "Add New Reflection";
  loadCategoriesForReflectionForm();
  document.getElementById("reflectionModal").classList.add("active");
}

function editReflection(reflectionId) {
  editingReflectionId = reflectionId;
  apiRequest("/get_reflections.php", "GET")
    .then((result) => {
      const reflection = result.data.reflections.find(
        (r) => r.id == reflectionId
      );
      if (reflection) {
        document.querySelector('#reflectionForm input[name="title"]').value =
          reflection.title;
        document.querySelector(
          '#reflectionForm textarea[name="content"]'
        ).value = reflection.content;
        document.querySelector(
          '#reflectionForm select[name="category"]'
        ).value = reflection.category;
        document.querySelector(
          '#reflectionForm input[name="image_url"]'
        ).value = reflection.image_url || "";

        // Show image preview if available
        if (reflection.image_url) {
          const preview = document.getElementById("imagePreview");
          preview.src = reflection.image_url;
          preview.style.display = "block";
        }

        document.querySelector(
          "#reflectionModal .modal-header h2"
        ).textContent = "Edit Reflection";
        document.getElementById("reflectionModal").classList.add("active");
      }
    })
    .catch((error) => {
      showAlert("Failed to load reflection details", "danger");
      console.error("Load reflection error:", error);
    });
}

async function saveReflection(event) {
  event.preventDefault();
  const formData = new FormData(event.target);
  const data = {
    title: formData.get("title"),
    content: formData.get("content"),
    category: formData.get("category"),
    image_url: formData.get("image_url"),
  };

  try {
    const endpoint = editingReflectionId
      ? `/update_reflection.php?id=${editingReflectionId}`
      : "/add_reflection.php";
    const method = "POST";

    await apiRequest(endpoint, method, data);
    showAlert(
      editingReflectionId
        ? "Reflection updated successfully!"
        : "Reflection added successfully!",
      "success"
    );
    closeModal("reflectionModal");
    event.target.reset();
    editingReflectionId = null;
    loadReflections();
  } catch (error) {
    console.error("Save reflection error:", error);
    showAlert("Failed to save reflection. Please try again.", "danger");
  }
}

async function deleteReflection(reflectionId) {
  if (!confirm("هل أنت متأكد من حذف هذا التأمل؟")) {
    return;
  }

  try {
    await apiRequest(`/delete_reflection.php?id=${reflectionId}`, "DELETE");
    showAlert("تم حذف التأمل بنجاح!", "success");
    loadReflections();
  } catch (error) {
    console.error("Delete reflection error:", error);
    showAlert("فشل حذف التأمل. حاول مرة أخرى.", "danger");
  }
}

// Image preview function for reflections
function previewReflectionImage() {
  const imageUrl = document.querySelector(
    '#reflectionForm input[name="image_url"]'
  ).value;
  const preview = document.getElementById("imagePreview");

  if (imageUrl) {
    preview.src = imageUrl;
    preview.style.display = "block";
  } else {
    preview.style.display = "none";
  }
}

// Users Functions
let editingUserId = null;

async function loadUsers() {
  try {
    const result = await apiRequest("/get_user.php", "GET");
    const users = result.data?.users || [];

    const tableHTML = `
            <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${users
                      .map(
                        (user) => `
                        <tr>
                            <td>${user.id}</td>
                            <td>${user.name}</td>
                            <td>${user.email}</td>
                            <td>${user.phone || "-"}</td>
                            <td><span class="badge badge-${
                              user.role === "admin"
                                ? "danger"
                                : user.role === "servant"
                                ? "warning"
                                : "info"
                            }">${user.role}</span></td>
                            <td><span class="badge badge-${
                              user.is_active == 1 ? "success" : "danger"
                            }">${
                          user.is_active == 1 ? "Active" : "Inactive"
                        }</span></td>
                            <td>
                                <button class="btn btn-warning btn-sm" onclick="editUser(${
                                  user.id
                                })">Edit</button>
                                <button class="btn btn-danger btn-sm" onclick="deleteUser(${
                                  user.id
                                })">Delete</button>
                                <button class="btn btn-${
                                  user.is_active == 1 ? "danger" : "success"
                                } btn-sm" onclick="toggleUserStatus(${
                          user.id
                        })">${
                          user.is_active == 1 ? "Deactivate" : "Activate"
                        }</button>
                            </td>
                        </tr>
                    `
                      )
                      .join("")}
                </tbody>
            </table>
            </div>
        `;

    document.getElementById("usersTableContainer").innerHTML = tableHTML;
  } catch (error) {
    document.getElementById("usersTableContainer").innerHTML =
      '<p class="text-center">Failed to load users</p>';
    console.error("Load users error:", error);
  }
}

function showAddUserModal() {
  editingUserId = null;
  document.getElementById("userForm").reset();
  document.querySelector("#userModal .modal-header h2").textContent =
    "Add New User";
  document.getElementById("userModal").classList.add("active");
}

function editUser(userId) {
  editingUserId = userId;
  apiRequest("/get_user.php", "GET")
    .then((result) => {
      const user = result.data.users.find((u) => u.id == userId);
      if (user) {
        document.querySelector('#userForm input[name="name"]').value =
          user.name;
        document.querySelector('#userForm input[name="email"]').value =
          user.email;
        document.querySelector('#userForm input[name="phone"]').value =
          user.phone || "";
        document.querySelector('#userForm select[name="role"]').value =
          user.role;
        document.querySelector('#userForm input[name="is_active"]').checked =
          user.is_active == 1;
        document.querySelector("#userModal .modal-header h2").textContent =
          "Edit User";
        document.getElementById("userModal").classList.add("active");
      }
    })
    .catch((error) => {
      showAlert("Failed to load user details", "danger");
      console.error("Load user error:", error);
    });
}

async function saveUser(event) {
  event.preventDefault();
  const formData = new FormData(event.target);
  const data = {
    name: formData.get("name"),
    email: formData.get("email"),
    phone: formData.get("phone"),
    role: formData.get("role"),
    is_active: formData.get("is_active") ? 1 : 0,
  };

  // Add password only for new users
  if (!editingUserId) {
    data.password = formData.get("password");
  }

  try {
    const endpoint = editingUserId
      ? `/update_user.php?id=${editingUserId}`
      : "/register.php";
    const method = "POST";

    await apiRequest(endpoint, method, data);
    showAlert(
      editingUserId ? "User updated successfully!" : "User added successfully!",
      "success"
    );
    closeModal("userModal");
    event.target.reset();
    editingUserId = null;
    loadUsers();
  } catch (error) {
    console.error("Save user error:", error);
    showAlert("Failed to save user. Please try again.", "danger");
  }
}

async function deleteUser(userId) {
  if (!confirm("هل أنت متأكد من حذف هذا المستخدم؟")) {
    return;
  }

  try {
    await apiRequest(`/delete_user.php?id=${userId}`, "DELETE");
    showAlert("تم حذف المستخدم بنجاح!", "success");
    loadUsers();
  } catch (error) {
    console.error("Delete user error:", error);
    showAlert("فشل حذف المستخدم. حاول مرة أخرى.", "danger");
  }
}

async function toggleUserStatus(userId) {
  try {
    await apiRequest(`/toggle_user_status.php?id=${userId}`, "POST");
    showAlert("User status updated successfully!", "success");
    loadUsers();
  } catch (error) {
    console.error("Toggle user status error:", error);
    showAlert("Failed to update user status. Please try again.", "danger");
  }
}

// Shared Barcodes Functions
async function loadSharedBarcodes() {
  try {
    const result = await apiRequest("/get_shared_barcodes.php");
    const barcodes = result.data.shared_barcodes || [];

    const container = document.getElementById("barcodesContainer");

    if (barcodes.length === 0) {
      container.innerHTML =
        '<p class="text-center">لا توجد باركودات مشتركة</p>';
      return;
    }

    let html =
      '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; padding: 20px;">';

    barcodes.forEach((barcode) => {
      const typeColors = {
        mass: "#8B0000",
        tasbeha: "#D4AF37",
        meeting: "#4CAF50",
        activity: "#800020",
      };

      const typeIcons = {
        mass: "⛪",
        tasbeha: "🕯️",
        meeting: "👥",
        activity: "🎯",
      };

      const typeLabels = {
        mass: "القداس الإلهي",
        tasbeha: "صلاة التسبحة",
        meeting: "الاجتماع الروحي",
        activity: "النشاط الكنسي",
      };

      html += `
        <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-top: 4px solid ${
          typeColors[barcode.event_type]
        };">
          <div style="text-align: center; margin-bottom: 15px;">
            <div style="font-size: 48px; margin-bottom: 10px;">
              ${typeIcons[barcode.event_type]}
            </div>
            <h3 style="color: ${
              typeColors[barcode.event_type]
            }; margin: 0 0 10px 0; font-size: 24px;">
              ${barcode.arabic_name || typeLabels[barcode.event_type]}
            </h3>
            <p style="color: #666; font-size: 14px; margin: 0 0 15px 0; line-height: 1.5;">
              ${
                barcode.description ||
                "باركود مشترك لجميع فعاليات " +
                  (barcode.arabic_name || typeLabels[barcode.event_type])
              }
            </p>
            <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; font-family: monospace; font-size: 16px; font-weight: bold; color: #333;">
              ${barcode.barcode}
            </div>
          </div>

          <div style="border-top: 1px solid #eee; padding-top: 15px; margin-top: 15px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
              <span style="color: #666;">إجمالي الفعاليات:</span>
              <strong>${barcode.total_events || 0}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
              <span style="color: #666;">الحضور الفريد:</span>
              <strong>${barcode.total_unique_attendees || 0}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
              <span style="color: #666;">إجمالي السجلات:</span>
              <strong>${barcode.total_attendance_records || 0}</strong>
            </div>
            ${
              barcode.last_scan_time
                ? `
            <div style="display: flex; justify-content: space-between; margin-top: 12px; padding-top: 12px; border-top: 1px solid #eee;">
              <span style="color: #666;">آخر مسح:</span>
              <strong style="color: #4CAF50; font-size: 12px;">${new Date(
                barcode.last_scan_time
              ).toLocaleString("ar-EG")}</strong>
            </div>
            `
                : ""
            }
          </div>

          <div style="margin-top: 15px; text-align: center;">
            <button class="btn btn-primary btn-sm" onclick="generateSharedBarcodeQR('${
              barcode.barcode
            }', '${barcode.arabic_name}')" style="width: 100%;">
              📱 إنشاء QR Code
            </button>
          </div>
        </div>
      `;
    });

    html += "</div>";
    container.innerHTML = html;
  } catch (error) {
    console.error("Load shared barcodes error:", error);
    document.getElementById("barcodesContainer").innerHTML =
      '<p class="text-center">فشل تحميل الباركودات المشتركة</p>';
  }
}

function generateSharedBarcodeQR(barcode, arabicName) {
  // Generate QR code for shared barcode
  const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=${encodeURIComponent(
    barcode
  )}`;

  const modalHTML = `
    <div class="modal active" id="qrModal" style="z-index: 10000;">
      <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
          <h2>QR Code - ${arabicName}</h2>
          <span class="close-modal" onclick="closeModal('qrModal')">&times;</span>
        </div>
        <div style="padding: 20px; text-align: center;">
          <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 15px;">
            <img src="${qrUrl}" alt="QR Code" style="max-width: 100%; border-radius: 8px;" />
          </div>
          <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
            <strong>الباركود:</strong>
            <div style="font-family: monospace; font-size: 16px; margin-top: 5px; color: #8B0000;">
              ${barcode}
            </div>
          </div>
          <div style="background: #fff3cd; padding: 15px; border-radius: 8px; text-align: right;">
            <strong style="color: #856404;">📋 ملاحظة:</strong>
            <p style="margin: 10px 0 0 0; color: #856404;">
              يمكن استخدام هذا الباركود لجميع فعاليات ${arabicName}. 
              سيتم تسجيل الحضور تلقائياً بالتاريخ والوقت الفعلي للمسح.
            </p>
          </div>
          <div style="margin-top: 20px;">
            <a href="${qrUrl}" download="${barcode}.png" class="btn btn-success">
              💾 تحميل الصورة
            </a>
            <button class="btn btn-primary" onclick="window.print()">
              🖨️ طباعة
            </button>
            <button class="btn btn-danger" onclick="closeModal('qrModal')">
              إغلاق
            </button>
          </div>
        </div>
      </div>
    </div>
  `;

  // Remove existing modal if any
  const existingModal = document.getElementById("qrModal");
  if (existingModal) {
    existingModal.remove();
  }

  // Add modal to body
  document.body.insertAdjacentHTML("beforeend", modalHTML);
}

// Modal Functions
function closeModal(modalId) {
  document.getElementById(modalId).classList.remove("active");
}

// Logout
function logout() {
  if (confirm("هل أنت متأكد من تسجيل الخروج؟")) {
    logoutUser();
  }
}

// Close modal when clicking outside
document.querySelectorAll(".modal").forEach((modal) => {
  modal.addEventListener("click", function (e) {
    if (e.target === this) {
      this.classList.remove("active");
    }
  });
});

// ============================================
// Reflection Categories Management Functions
// ============================================

// Load reflection categories
async function loadCategories() {
  const container = document.getElementById("categoriesTableContainer");
  container.innerHTML =
    '<div class="loading"><div class="spinner"></div><p>جاري تحميل التصنيفات...</p></div>';

  try {
    const response = await fetch(
      API_BASE_URL + "/get_reflection_categories.php"
    );
    const result = await response.json();

    if (!result.success) {
      throw new Error(result.message);
    }

    const categories = result.data.categories;

    if (categories.length === 0) {
      container.innerHTML = '<p class="text-center">لا توجد تصنيفات حالياً</p>';
      return;
    }

    let html = `
      <div class="table-container">
      <table class="table">
        <thead>
          <tr>
            <th>الترتيب</th>
            <th>الأيقونة</th>
            <th>الاسم العربي</th>
            <th>الاسم الإنجليزي</th>
            <th>الوصف</th>
            <th>اللون</th>
            <th>الحالة</th>
            <th>عدد التأملات</th>
            <th>الإجراءات</th>
          </tr>
        </thead>
        <tbody>
    `;

    for (const category of categories) {
      // Get reflection count for this category
      const reflectionsResponse = await fetch(
        API_BASE_URL +
          `/get_reflections.php?category=${encodeURIComponent(
            category.name_en
          )}`
      );
      const reflectionsData = await reflectionsResponse.json();
      const reflectionCount = reflectionsData.success
        ? reflectionsData.data.count
        : 0;

      html += `
        <tr>
          <td><strong>${category.display_order}</strong></td>
          <td style="font-size: 24px">${category.icon}</td>
          <td><strong>${category.name_ar}</strong></td>
          <td>${category.name_en}</td>
          <td style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
            ${category.description || "-"}
          </td>
          <td>
            <span style="display: inline-block; width: 30px; height: 30px; background: ${
              category.color
            }; border-radius: 4px; border: 1px solid #ddd;"></span>
            <small style="display: block; color: #666;">${
              category.color
            }</small>
          </td>
          <td>
            ${
              category.is_active == 1
                ? '<span class="badge badge-success">نشط</span>'
                : '<span class="badge badge-secondary">معطل</span>'
            }
          </td>
          <td><span class="badge badge-info">${reflectionCount}</span></td>
          <td>
            <button class="btn btn-warning btn-sm" onclick='editCategory(${JSON.stringify(
              category
            ).replace(/'/g, "&apos;")})' title="تعديل">
              ✏️
            </button>
            <button class="btn btn-danger btn-sm" onclick="deleteCategory(${
              category.id
            }, '${category.name_ar}', ${reflectionCount})" title="حذف">
              🗑️
            </button>
          </td>
        </tr>
      `;
    }

    html += "</tbody></table></div>";
    container.innerHTML = html;
  } catch (error) {
    console.error("Load categories error:", error);
    container.innerHTML =
      '<p class="text-center">فشل تحميل التصنيفات: ' + error.message + "</p>";
  }
}

// Show add category modal
function showAddCategoryModal() {
  document.getElementById("categoryModalTitle").textContent =
    "إضافة تصنيف جديد";
  document.getElementById("categoryForm").reset();
  document.querySelector('[name="id"]').value = "";
  document.querySelector('[name="is_active"]').checked = true;
  document.querySelector('[name="color"]').value = "#8B0000";
  document.getElementById("colorHex").value = "#8B0000";
  document.getElementById("colorPreview").style.color = "#8B0000";
  document.getElementById("categoryModal").classList.add("active");
}

// Edit category
function editCategory(category) {
  document.getElementById("categoryModalTitle").textContent = "تعديل التصنيف";
  document.querySelector('[name="id"]').value = category.id;
  document.querySelector('[name="name_ar"]').value = category.name_ar;
  document.querySelector('[name="name_en"]').value = category.name_en;
  document.querySelector('[name="description"]').value =
    category.description || "";
  document.querySelector('[name="icon"]').value = category.icon;
  document.querySelector('[name="color"]').value = category.color;
  document.getElementById("colorHex").value = category.color;
  document.getElementById("colorPreview").style.color = category.color;
  document.querySelector('[name="display_order"]').value =
    category.display_order;
  document.querySelector('[name="is_active"]').checked =
    category.is_active == 1;
  document.getElementById("categoryModal").classList.add("active");
}

// Save category (add or update)
async function saveCategory(event) {
  event.preventDefault();

  const form = event.target;
  const formData = new FormData(form);

  const categoryData = {
    name_ar: formData.get("name_ar"),
    name_en: formData.get("name_en"),
    description: formData.get("description"),
    icon: formData.get("icon"),
    color: formData.get("color"),
    display_order: parseInt(formData.get("display_order")),
    is_active: formData.get("is_active") ? 1 : 0,
  };

  const id = formData.get("id");
  const isEdit = id && id !== "";

  if (isEdit) {
    categoryData.id = parseInt(id);
  }

  try {
    const endpoint = isEdit
      ? "/update_reflection_category.php"
      : "/add_reflection_category.php";

    const response = await fetch(API_BASE_URL + endpoint, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${authToken}`,
      },
      body: JSON.stringify(categoryData),
    });

    const result = await response.json();

    if (!result.success) {
      throw new Error(result.message);
    }

    showAlert(
      isEdit ? "تم تحديث التصنيف بنجاح" : "تم إضافة التصنيف بنجاح",
      "success"
    );
    closeModal("categoryModal");
    loadCategories();

    // Reload categories in reflection form
    loadCategoriesForReflectionForm();
  } catch (error) {
    console.error("Save category error:", error);
    showAlert("خطأ: " + error.message, "danger");
  }
}

// Delete category
async function deleteCategory(id, name, reflectionCount) {
  if (reflectionCount > 0) {
    showAlert(
      `لا يمكن حذف التصنيف "${name}" لأنه يحتوي على ${reflectionCount} تأمل. قم بحذف أو نقل التأملات أولاً.`,
      "warning"
    );
    return;
  }

  if (
    !confirm(
      `هل أنت متأكد من حذف التصنيف "${name}"؟\n\nهذا الإجراء لا يمكن التراجع عنه.`
    )
  ) {
    return;
  }

  try {
    const response = await fetch(
      API_BASE_URL + "/delete_reflection_category.php",
      {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${authToken}`,
        },
        body: JSON.stringify({ id }),
      }
    );

    const result = await response.json();

    if (!result.success) {
      throw new Error(result.message);
    }

    showAlert("تم حذف التصنيف بنجاح", "success");
    loadCategories();

    // Reload categories in reflection form
    loadCategoriesForReflectionForm();
  } catch (error) {
    console.error("Delete category error:", error);
    showAlert("خطأ: " + error.message, "danger");
  }
}

// Load categories for reflection form dropdown
async function loadCategoriesForReflectionForm() {
  try {
    const response = await fetch(
      API_BASE_URL + "/get_reflection_categories.php?active_only=true"
    );
    const result = await response.json();

    if (!result.success) {
      throw new Error(result.message);
    }

    const categories = result.data.categories;
    const select = document.getElementById("reflectionCategorySelect");

    if (!select) return;

    let html = '<option value="">-- اختر التصنيف --</option>';

    categories.forEach((category) => {
      html += `<option value="${category.name_en}">${category.icon} ${category.name_ar}</option>`;
    });

    select.innerHTML = html;
  } catch (error) {
    console.error("Load categories for form error:", error);
  }
}

// Initialize categories in reflection form on page load
setTimeout(function () {
  loadCategoriesForReflectionForm();
}, 1000);

// ==========================================
// NOTIFICATIONS MANAGEMENT
// ==========================================

// Load notifications
async function loadNotifications(status = "all") {
  const container = document.getElementById("notificationsTableContainer");

  try {
    container.innerHTML = `
      <div class="loading">
        <div class="spinner"></div>
        <p>جاري تحميل الإشعارات...</p>
      </div>
    `;

    const response = await fetch(
      API_BASE_URL + `/get_notifications.php?status=${status}`,
      {
        headers: {
          Authorization: `Bearer ${authToken}`,
        },
      }
    );

    const result = await response.json();

    if (!result.success) {
      throw new Error(result.message);
    }

    const notifications = result.notifications;
    const stats = result.stats;

    // Update stats
    updateNotificationStats(stats);

    // Display notifications table
    let html = `
      <div class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th style="width: 50px;">#</th>
              <th style="min-width: 300px;">العنوان والمحتوى</th>
              <th style="width: 120px;">الفئة المستهدفة</th>
              <th style="width: 100px;">الحالة</th>
              <th style="width: 140px;">الإحصائيات</th>
              <th style="width: 100px;">التكرار</th>
              <th style="width: 150px;">الموعد القادم</th>
              <th style="width: 100px;">الإجراءات</th>
            </tr>
          </thead>
          <tbody>
    `;

    if (notifications.length === 0) {
      html += `
        <tr>
          <td colspan="8" style="text-align: center; padding: 40px; color: #999;">
            <div style="font-size: 48px; margin-bottom: 10px;">📭</div>
            <div style="font-size: 16px;">لا توجد إشعارات</div>
          </td>
        </tr>
      `;
    } else {
      notifications.forEach((notif) => {
        const statusBadge = notif.is_sent
          ? '<span class="badge badge-success">✓ مرسل</span>'
          : notif.scheduled_at
          ? '<span class="badge badge-warning">🕐 مجدول</span>'
          : '<span class="badge badge-secondary">⏳ معلق</span>';

        const repeatBadge =
          notif.repeat_type === "none"
            ? '<span style="color: #999;">-</span>'
            : `<span class="repeat-badge">${
                notif.repeat_type === "daily"
                  ? "� يومي"
                  : notif.repeat_type === "weekly"
                  ? "� أسبوعي"
                  : "� شهري"
              }</span>`;

        const targetDisplay =
          notif.target_users === "all"
            ? '<span class="target-badge">👥 الكل</span>'
            : notif.target_users === "members"
            ? '<span class="target-badge">👤 المخدومين</span>'
            : notif.target_users === "servants"
            ? '<span class="target-badge">🙏 الخدام</span>'
            : notif.target_users === "admins"
            ? '<span class="target-badge">👑 المسؤولين</span>'
            : '<span class="target-badge">🎯 مخصص</span>';

        const typeEmoji =
          notif.type === "reflection"
            ? "📖"
            : notif.type === "announcement"
            ? "📢"
            : notif.type === "event"
            ? "📅"
            : notif.type === "system"
            ? "⚙️"
            : "💬";

        const readPercentage =
          notif.sent_count > 0
            ? Math.round((notif.read_count / notif.sent_count) * 100)
            : 0;

        const percentageClass =
          readPercentage >= 70
            ? "high"
            : readPercentage >= 40
            ? "medium"
            : "low";

        const nextSendDisplay = notif.next_send_at
          ? `<div class="next-send-time">📅 ${notif.next_send_at}</div>`
          : '<span style="color: #999;">-</span>';

        html += `
          <tr>
            <td class="notification-table-cell compact">
              <span class="notification-id">#${notif.id}</span>
            </td>
            <td class="notification-table-cell">
              <div class="notification-title">
                <span class="type-emoji">${typeEmoji}</span>
                ${notif.title}
              </div>
              <div class="notification-body">
                ${
                  notif.body.length > 80
                    ? notif.body.substring(0, 80) + "..."
                    : notif.body
                }
              </div>
            </td>
            <td class="notification-table-cell compact">
              ${targetDisplay}
            </td>
            <td class="notification-table-cell compact">
              ${statusBadge}
            </td>
            <td class="notification-table-cell compact">
              <div class="notification-stats">
                <div>
                  <div style="font-size: 11px; color: #999;">إرسال</div>
                  <div style="font-weight: 600;">${notif.sent_count || 0}</div>
                </div>
                <div>
                  <div style="font-size: 11px; color: #999;">قراءة</div>
                  <div style="font-weight: 600;">
                    ${notif.read_count || 0}
                    ${
                      notif.sent_count > 0
                        ? `<span class="read-percentage ${percentageClass}">${readPercentage}%</span>`
                        : ""
                    }
                  </div>
                </div>
              </div>
            </td>
            <td class="notification-table-cell compact">
              ${repeatBadge}
            </td>
            <td class="notification-table-cell compact">
              ${nextSendDisplay}
            </td>
            <td class="notification-table-cell compact">
              <div class="notification-actions">
                <button class="btn btn-sm btn-danger" 
                        onclick="deleteNotification(${notif.id})"
                        title="حذف الإشعار">
                  🗑️
                </button>
              </div>
            </td>
          </tr>
        `;
      });
    }

    html += `
          </tbody>
        </table>
      </div>
    `;

    container.innerHTML = html;
  } catch (error) {
    console.error("Load notifications error:", error);
    container.innerHTML = `
      <div class="error-message">
        خطأ في تحميل الإشعارات: ${error.message}
      </div>
    `;
  }
}

// Update notification statistics
function updateNotificationStats(stats) {
  const statsContainer = document.getElementById("notificationStats");

  if (!statsContainer || !stats) return;

  const html = `
    <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
      <div class="stat-card">
        <div class="stat-icon" style="background-color: #2196F3;">📊</div>
        <div class="stat-info">
          <h3>${stats.total || 0}</h3>
          <p>إجمالي الإشعارات</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background-color: #4CAF50;">✓</div>
        <div class="stat-info">
          <h3>${stats.sent || 0}</h3>
          <p>المرسلة</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background-color: #FF9800;">🕐</div>
        <div class="stat-info">
          <h3>${stats.scheduled || 0}</h3>
          <p>المجدولة</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background-color: #9C27B0;">🔄</div>
        <div class="stat-info">
          <h3>${stats.recurring || 0}</h3>
          <p>المتكررة</p>
        </div>
      </div>
    </div>
  `;

  statsContainer.innerHTML = html;
}

// Show add notification modal
async function showAddNotificationModal() {
  const modal = document.getElementById("notificationModal");
  const form = document.getElementById("notificationForm");

  form.reset();
  document.getElementById("notificationModalTitle").textContent =
    "إنشاء إشعار جديد";

  // Load users for specific targeting
  await loadUsersForNotification();

  modal.classList.add("active");
}

// Load users for notification targeting
async function loadUsersForNotification() {
  try {
    // get_user.php without user_id returns all users for admin
    const response = await fetch(API_BASE_URL + "/get_user.php", {
      headers: {
        Authorization: `Bearer ${authToken}`,
      },
    });

    const result = await response.json();

    if (result.success && result.data && result.data.users) {
      const select = document.getElementById("specificUsersList");
      let html = "";

      result.data.users.forEach((user) => {
        html += `<option value="${user.id}">${user.name} (${user.email})</option>`;
      });

      select.innerHTML = html;
    }
  } catch (error) {
    console.error("Load users error:", error);
  }
}

// Toggle schedule fields
function toggleScheduleFields() {
  const sendImmediately = document.querySelector(
    '[name="send_immediately"]'
  ).checked;
  const scheduleFields = document.getElementById("scheduleFields");

  if (sendImmediately) {
    scheduleFields.style.display = "none";
    document.querySelector('[name="scheduled_at"]').removeAttribute("required");
  } else {
    scheduleFields.style.display = "block";
    document
      .querySelector('[name="scheduled_at"]')
      .setAttribute("required", "required");
  }
}

// Toggle specific users field
function toggleSpecificUsers() {
  const targetUsers = document.querySelector('[name="target_users"]').value;
  const specificUsersField = document.getElementById("specificUsersField");

  if (targetUsers === "specific") {
    specificUsersField.style.display = "block";
    document
      .querySelector('[name="specific_user_ids"]')
      .setAttribute("required", "required");
  } else {
    specificUsersField.style.display = "none";
    document
      .querySelector('[name="specific_user_ids"]')
      .removeAttribute("required");
  }
}

// Save notification
async function saveNotification(event) {
  event.preventDefault();

  const form = event.target;
  const formData = new FormData(form);

  const notificationData = {
    title: formData.get("title"),
    body: formData.get("body"),
    type: formData.get("type"),
    priority: formData.get("priority"),
    send_immediately: formData.get("send_immediately") === "1",
    scheduled_at: formData.get("scheduled_at") || null,
    repeat_type: formData.get("repeat_type") || "none",
    target_users: formData.get("target_users"),
  };

  // Handle specific users
  if (notificationData.target_users === "specific") {
    const selectedUsers = Array.from(
      form.querySelectorAll('[name="specific_user_ids"] option:checked')
    ).map((option) => parseInt(option.value));

    if (selectedUsers.length === 0) {
      showAlert("يرجى اختيار مستخدم واحد على الأقل", "danger");
      return;
    }

    notificationData.specific_user_ids = selectedUsers;
  }

  console.log("📤 Sending notification:", notificationData);

  try {
    const response = await fetch(API_BASE_URL + "/create_notification.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${authToken}`,
      },
      body: JSON.stringify(notificationData),
    });

    console.log("📥 Response status:", response.status);
    const result = await response.json();
    console.log("📥 Response data:", result);

    if (!result.success) {
      throw new Error(result.message);
    }

    showAlert(
      notificationData.send_immediately
        ? `تم إرسال الإشعار بنجاح إلى ${result.sent_to || 0} مستخدم`
        : "تم جدولة الإشعار بنجاح",
      "success"
    );

    closeModal("notificationModal");
    loadNotifications();
  } catch (error) {
    console.error("❌ Save notification error:", error);
    showAlert("خطأ: " + error.message, "danger");
  }
}

// Filter notifications with button highlighting
function filterNotifications(status, buttonElement) {
  // Remove active class from all filter buttons
  document.querySelectorAll('[id^="filter"]').forEach((btn) => {
    btn.classList.remove("btn-primary");
    if (btn.id === "filterSent") {
      btn.style.background = "#4caf50";
      btn.style.color = "white";
    } else if (btn.id === "filterScheduled") {
      btn.style.background = "#ff9800";
      btn.style.color = "white";
    }
  });

  // Add active class to clicked button
  buttonElement.classList.add("btn-primary");
  buttonElement.style.background = "";
  buttonElement.style.color = "";

  // Load notifications with filter
  loadNotifications(status);
}

// Delete notification
async function deleteNotification(notificationId) {
  if (!confirm("هل أنت متأكد من حذف هذا الإشعار؟")) {
    return;
  }

  try {
    const response = await fetch(API_BASE_URL + "/delete_notification.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${authToken}`,
      },
      body: JSON.stringify({ id: notificationId }),
    });

    const result = await response.json();

    if (!result.success) {
      throw new Error(result.message);
    }

    showAlert("تم حذف الإشعار بنجاح", "success");
    loadNotifications();
  } catch (error) {
    console.error("Delete notification error:", error);
    showAlert("خطأ: " + error.message, "danger");
  }
}
