
Name: app-file-scan
Epoch: 1
Version: 1.4.41
Release: 1%{dist}
Summary: Antimalware File Scan
License: GPLv3
Group: ClearOS/Apps
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = 1:%{version}-%{release}
Requires: app-base

%description
The File Scan app scans the server's file system for infected files.

%package core
Summary: Antimalware File Scan - Core
License: LGPLv3
Group: ClearOS/Libraries
Requires: app-base-core
Requires: app-tasks-core
Requires: app-antivirus-core
Requires: app-mail-notification

%description core
The File Scan app scans the server's file system for infected files.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/file_scan
cp -r * %{buildroot}/usr/clearos/apps/file_scan/

install -d -m 0755 %{buildroot}/var/clearos/file_scan
install -D -m 0644 packaging/app-file-scan.cron %{buildroot}/etc/cron.d/app-file-scan
install -D -m 0755 packaging/file_scan %{buildroot}/usr/sbin/file_scan
install -D -m 0644 packaging/file_scan.conf %{buildroot}/etc/clearos/file_scan.conf

%post
logger -p local6.notice -t installer 'app-file-scan - installing'

%post core
logger -p local6.notice -t installer 'app-file-scan-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/file_scan/deploy/install ] && /usr/clearos/apps/file_scan/deploy/install
fi

[ -x /usr/clearos/apps/file_scan/deploy/upgrade ] && /usr/clearos/apps/file_scan/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-file-scan - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-file-scan-core - uninstalling'
    [ -x /usr/clearos/apps/file_scan/deploy/uninstall ] && /usr/clearos/apps/file_scan/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/file_scan/controllers
/usr/clearos/apps/file_scan/htdocs
/usr/clearos/apps/file_scan/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/file_scan/packaging
%dir /usr/clearos/apps/file_scan
%dir /var/clearos/file_scan
/usr/clearos/apps/file_scan/deploy
/usr/clearos/apps/file_scan/language
/usr/clearos/apps/file_scan/libraries
/etc/cron.d/app-file-scan
/usr/sbin/file_scan
%attr(0644,webconfig,webconfig) %config(noreplace) /etc/clearos/file_scan.conf
