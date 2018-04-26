Name:		afi
Version:	0.7
Release:	1%{?dist}
Summary:	AFI Automatically Fully Install - a wrapper around kickstart

Group:		Applications/System
License:	Apache License, Version 2.0
URL:		https://github.com/hoonetorg/%{name}
Source0:	https://github.com/hoonetorg/%{name}/archive/v%{version}.tar.gz#/%{name}-%{version}.tar.gz

BuildRequires:	rpm-build
Requires:	webserver, php
Requires(post): policycoreutils-python
Requires(postun): policycoreutils-python

%description

%clean
rm -rf %{buildroot}

%prep
%setup -q

%install
rm -rf %{buildroot}
mkdir %{buildroot}

mkdir -p -m0755 %{buildroot}/var/
mkdir -p -m0755 %{buildroot}/var/www/
cp -rp %{name} %{buildroot}/var/www/

install -d -m0755 %{buildroot}%{_sysconfdir}/%{name}

install -d -m0755 %{buildroot}%{_sysconfdir}/httpd/conf.d
install -p -m0644 httpd.conf %{buildroot}%{_sysconfdir}/httpd/conf.d/%{name}.conf

%files
%defattr(-,root,root,-)

/var/www
%doc COPYRIGHT LICENSE README* 
%config(noreplace) /var/www/%{name}/%{name}.ini

#%config(noreplace) %{_sysconfdir}/%{name}/*
%dir %attr(-,apache,apache) %{_sysconfdir}/%{name}

%dir %{_sysconfdir}/httpd
%dir %{_sysconfdir}/httpd/conf.d
%config(noreplace) %{_sysconfdir}/httpd/conf.d/%{name}.conf

%post
semanage fcontext -a -t httpd_sys_rw_content_t '%{_sysconfdir}/%{name}(/.*)?' 2>/dev/null || :
restorecon -R %{_sysconfdir}/%{name} || :

%postun
if [ $1 -eq 0 ] ; then  # final removal
semanage fcontext -d -t httpd_sys_rw_content_t '%{_sysconfdir}/%{name}(/.*)?' 2>/dev/null || :
fi

%changelog

